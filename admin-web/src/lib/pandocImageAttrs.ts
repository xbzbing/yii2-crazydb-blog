/**
 * Pandoc link_attributes 图片扩展语法的编辑器显示层支持。
 *
 * 后端（App\Post\PandocAttributes\PandocAttributesParser）负责把
 * `![alt](img.jpg){width=50% height=200 #fig1 .left}` 渲染成真实尺寸；
 * 本模块在 Vditor IR 编辑器里做**纯显示层**的同款解析：Lute 不认识该语法
 * （`{…}` 会原样渲染成图片 span 后的兄弟文本节点），我们解析该文本节点并把
 * 尺寸/样式应用到 `<img>` 元素上，让用户在编辑器里所见即所得。
 *
 * 明确约束（均经 Lute VditorIRDOM2Md 实测验证）：
 * - 绝不修改/删除 `{…}` 文本节点——IR→MD 序列化会丢失属性（数据丢失）；
 *   只变异 <img> 的属性/class/style/alt/title，这些不参与序列化（安全）。
 * - 语法与后端保持同一套文法与单位语义（见各常量注释的 Pandoc 出处），
 *   差异点：本模块无 HTMLPurifier，宽度/高度超界不做白名单过滤（仅编辑器
 *   显示，保存的 markdown 仍由后端最终裁定）。
 */

/** 解析结果（与后端 ParsedAttributes 对齐） */
export interface PandocImageAttrs {
  id?: string
  /** `.class` 与 `class="a b"` 收集的类名（已去前导点） */
  classes: string[]
  /** 像素尺寸（无单位/px），应用为 width/height 属性 */
  pixelWidth?: string
  pixelHeight?: string
  /** 非像素尺寸（%/cm/mm/in/inch/pt/pc/em），应用为 style 声明 */
  styleDecls: string[]
  alt?: string
  title?: string
  /** `style="..."` 键的原始声明（与 styleDecls 合并，键的在先——对齐后端拼接顺序） */
  rawStyle: string[]
}

/**
 * 整个 `{...}` 块的匹配（对齐后端 PandocAttributesParser::BLOCK）：
 * 块结构不合法则整体不消费，文本保持原样。
 */
const BLOCK =
  /\{[\t ]*(?:(?:#[\w:.-]+|\.[\w:.-]+|[A-Za-z][\w-]*=(?:"[^"\n]*"|'[^'\n]*'|[^\s{}"'=]+))[\t ]*)*\}/

/** 块内逐 token 拆解（对齐后端 PandocAttributesParser::TOKEN） */
const TOKEN =
  /(?:#([\w:.-]+))|(?:\.([\w:.-]+))|([A-Za-z][\w-]*)=("[^"\n]*"|'[^'\n]*'|[^\s{}"'=]+)/g

/**
 * 尺寸值（对齐 Pandoc ImageSize.php toDim()：cm mm in inch % px "" pt pc em，无 ex；
 * 数字与单位间不允许空格；无单位视为像素）。
 */
const DIMENSION = /^(\d+(?:\.\d+)?)(inch|in|cm|mm|pt|pc|px|em|%)?$/i

/** 单个 {…} 块 → 属性；结构非法返回 null */
export function parseAttrBlock(block: string): PandocImageAttrs | null {
  // 必须整体恰好是一个块（防 `x{...}y` 之类的部分匹配被误判为合法块）
  const check = BLOCK.exec(block)
  if (!check || check.index !== 0 || check[0].length !== block.length) return null
  const attrs: PandocImageAttrs = { classes: [], styleDecls: [], rawStyle: [] }
  TOKEN.lastIndex = 0
  let matched = false
  let m: RegExpExecArray | null
  while ((m = TOKEN.exec(block)) !== null) {
    matched = true
    if (m[1] !== undefined) {
      attrs.id = m[1]
      continue
    }
    if (m[2] !== undefined) {
      attrs.classes.push(m[2])
      continue
    }
    const key = m[3]
    let value = m[4]
    if (value.startsWith('"') || value.startsWith("'")) value = value.slice(1, -1)
    switch (key) {
      case 'width':
      case 'height': {
        const dim = parseDimension(value)
        if (dim) {
          if ('pixel' in dim) {
            if (key === 'width') attrs.pixelWidth = dim.pixel
            else attrs.pixelHeight = dim.pixel
          } else {
            attrs.styleDecls.push(`${key}:${dim.style}`)
          }
        }
        break
      }
      case 'style':
        attrs.rawStyle.push(value)
        break
      case 'class':
        for (const cls of value.split(/\s+/)) if (cls) attrs.classes.push(cls)
        break
      case 'id':
        attrs.id = value
        break
      case 'alt':
        attrs.alt = value
        break
      case 'title':
        attrs.title = value
        break
      default:
        // 未知键忽略（对齐后端：HTMLPurifier 剥 data-*，透传无意义）
        break
    }
  }
  // `{}` 空块合法；有内容却零 token 视为非法（与后端一致）
  if (!matched && block.slice(1, -1).trim() !== '') return null
  return attrs
}

/** 尺寸值 → 像素属性或 style 声明（无效返回 null，与 Pandoc 丢弃语义一致） */
function parseDimension(value: string): { pixel: string } | { style: string } | null {
  const m = DIMENSION.exec(value)
  if (!m) return null
  const unit = (m[2] || '').toLowerCase()
  if (unit === '' || unit === 'px') {
    return { pixel: String(Math.round(Number(m[1]))) }
  }
  return { style: `${m[1]}${unit}` }
}

/** 从文本节点起始处连续解析多个相邻 `{…}` 块（Pandoc 允许连续属性块；隔空格即停） */
export function parseLeadingBlocks(text: string): PandocImageAttrs | null {
  let rest = text
  let merged: PandocImageAttrs | null = null
  for (;;) {
    const m = BLOCK.exec(rest)
    if (!m || m.index !== 0) break
    const attrs = parseAttrBlock(m[0])
    if (!attrs) break
    if (!merged) {
      merged = attrs
    } else {
      // 连续块合并：标量后者覆盖，class/style 累积（对齐后端逐块 apply 顺序）
      if (attrs.id !== undefined) merged.id = attrs.id
      if (attrs.alt !== undefined) merged.alt = attrs.alt
      if (attrs.title !== undefined) merged.title = attrs.title
      if (attrs.pixelWidth !== undefined) merged.pixelWidth = attrs.pixelWidth
      if (attrs.pixelHeight !== undefined) merged.pixelHeight = attrs.pixelHeight
      merged.classes.push(...attrs.classes)
      merged.styleDecls.push(...attrs.styleDecls)
      merged.rawStyle.push(...attrs.rawStyle)
    }
    rest = rest.slice(m[0].length)
  }
  return merged
}

/** 回滚显示层施加的变更所需的原始值快照 */
interface AppliedState {
  origAlt: string
  origTitle: string
  origId: string
  classes: string[]
  styleProps: string[]
  widthAttr: boolean
  heightAttr: boolean
}

const applied = new WeakMap<HTMLImageElement, AppliedState>()

function revert(img: HTMLImageElement): void {
  const state = applied.get(img)
  if (!state) return
  img.alt = state.origAlt
  img.title = state.origTitle
  img.id = state.origId
  for (const cls of state.classes) img.classList.remove(cls)
  for (const prop of state.styleProps) img.style.removeProperty(prop)
  if (state.widthAttr) img.removeAttribute('width')
  if (state.heightAttr) img.removeAttribute('height')
  applied.delete(img)
}

function applyOne(img: HTMLImageElement, attrs: PandocImageAttrs): void {
  revert(img)
  const state: AppliedState = {
    origAlt: img.alt,
    origTitle: img.title,
    origId: img.id,
    classes: [],
    styleProps: [],
    widthAttr: false,
    heightAttr: false,
  }
  if (attrs.pixelWidth !== undefined) {
    img.setAttribute('width', attrs.pixelWidth)
    state.widthAttr = true
  }
  if (attrs.pixelHeight !== undefined) {
    img.setAttribute('height', attrs.pixelHeight)
    state.heightAttr = true
  }
  const decls: string[] = []
  // rawStyle 可能是 `a:1;b:2` 复合声明，逐条拆开（浏览器 setProperty 不接受复合串）
  for (const raw of attrs.rawStyle) {
    for (const decl of raw.split(';')) if (decl.trim()) decls.push(decl.trim())
  }
  decls.push(...attrs.styleDecls)
  for (const decl of decls) {
    const idx = decl.indexOf(':')
    if (idx <= 0) continue
    img.style.setProperty(decl.slice(0, idx).trim().toLowerCase(), decl.slice(idx + 1).trim())
    state.styleProps.push(decl.slice(0, idx).trim().toLowerCase())
  }
  for (const cls of attrs.classes) {
    if (!img.classList.contains(cls)) {
      img.classList.add(cls)
      state.classes.push(cls)
    }
  }
  if (attrs.id !== undefined) img.id = attrs.id
  if (attrs.alt !== undefined) img.alt = attrs.alt
  if (attrs.title !== undefined) img.title = attrs.title
  applied.set(img, state)
}

/**
 * 扫描 IR DOM，把图片 span 后随的 `{…}` 文本应用为 <img> 显示属性。
 *
 * IR 结构（Lute 实测）：
 *   <span class="vditor-ir__node" data-type="img">…markers…<img></span>{width=50%} 文本
 * `{…}` 是 span 的兄弟文本节点；仅当它紧跟在 span 后（index 0 起连续块）才应用，
 * 与后端「属性必须紧跟图片」的 Pandoc 邻接规则一致。
 */
export function applyPandocImageAttrs(root: ParentNode): void {
  for (const span of root.querySelectorAll<HTMLElement>('.vditor-ir__node[data-type="img"]')) {
    const img = span.querySelector('img')
    if (!img) continue
    const next = span.nextSibling
    // 3 = Node.TEXT_NODE（DOM 规范常量；写死数值避免依赖全局 Node，便于非浏览器环境测试）
    if (!next || next.nodeType !== 3) {
      revert(img)
      continue
    }
    const attrs = parseLeadingBlocks(next.textContent || '')
    if (attrs) {
      applyOne(img, attrs)
    } else {
      revert(img)
    }
  }
}
