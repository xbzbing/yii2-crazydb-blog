/**
 * Pandoc `fenced_divs` 围栏 div 的编辑器**预览面板显示层**。
 *
 *     ::: {.center}
 *     ![示意图](test.png){width=70%}
 *     :::
 *
 * 后端（App\Post\FencedDiv）负责把围栏渲染成 `<div class="center">…</div>`；
 * Lute 不识别该语法——实测 Md2HTML 会把围栏行与内容混排进同一个 `<p>`（行间 `<br>`），
 * 或在空行分隔时输出独立的 `<p>::: {.center}</p>`。本模块把预览 DOM 重组为
 * 与后端一致的 div 包裹结构，让用户在预览里看到最终效果。
 *
 * 明确约束（对齐 pandocImageAttrs 模块的既有原则）：
 * - **只允许作用于只读预览面板**（.vditor-preview 的 .vditor-reset 内容区）。
 *   绝不触碰 contenteditable 源区（ir/sv/wysiwyg）：IR/序列化会丢内容。
 * - 解析语义与后端逐条对齐：开栏须带合法 `{…}` 属性块（复用同一 parseAttrBlock
 *   校验，`{note: hello}` 保持字面量）；裸 `:::` 仅作闭栏，游离闭栏保持字面量；
 *   等长嵌套按最内层配对（栈）；闭栏冒号数须不少于开栏；未闭合包裹到内容区末尾。
 * - 预览内容每次随 markdown 整体重渲染（innerHTML 重建），本模块的变换是
 *   幂等的：围栏标记被剥离后再次扫描不产生任何改动。
 * - 分组限制在内容区的直接子块之间（blockquote/list 内的围栏预览不做重组，
 *   保持字面量——后端仍会正确渲染，源码区也始终可见原文）。
 */

import { parseAttrBlock, type PandocImageAttrs } from './pandocImageAttrs'

/** 开栏行：`:::` + 合法属性块（整体须过 parseAttrBlock 校验）。
 *  允许四周空白：Lute 的 Md2HTML 会在 <br /> 后换行，围栏文本节点常带 \n（实测）。 */
const OPEN_LINE = /^[ \t\r\n]*(:{3,})[ \t\r\n]*(\{.*\})[ \t\r\n]*$/
/** 闭栏行：仅冒号（≥3）+ 周边空白 */
const CLOSE_LINE = /^[ \t\r\n]*(:{3,})[ \t\r\n]*$/

/** 一个分组：开栏 → 创建的 div（含所属父分组，用于同块内嵌套定位） */
interface Group {
  div: HTMLDivElement
  fenceLength: number
  parent: Group | null
  placed: boolean
}

/** 内容区直接子块被 <br> 切出的一个“行段” */
interface Unit {
  nodes: ChildNode[]
  /** 开栏标记（unit 即开栏行时指向其分组）；闭栏标记用 markerClose 表示 */
  markerOpen: Group | null
  markerClose: boolean
  /** 内容段所属分组（开栏前的内容为 null） */
  group: Group | null
}

interface Block {
  el: Element
  units: Unit[]
  hasMarker: boolean
}

/** 行段分类：返回 null = 普通内容 */
function classifySegment(nodes: ChildNode[]): { open: { fenceLength: number; attrs: PandocImageAttrs } | null; closeLength: number } | null {
  if (nodes.length === 0 || !nodes.every((n) => n.nodeType === 3)) {
    return null
  }
  const text = nodes.map((n) => n.textContent ?? '').join('')
  const open = OPEN_LINE.exec(text)
  if (open) {
    // 属性块须整体合法（与后端 parseAttributeBlock 同一套校验）
    const attrs = parseAttrBlock(open[2])
    if (attrs) {
      return { open: { fenceLength: open[1].length, attrs }, closeLength: 0 }
    }
    return null
  }
  const close = CLOSE_LINE.exec(text)
  if (close) {
    return { open: null, closeLength: close[1].length }
  }
  return null
}

/** 把内容区直接子块切成行段（顶层 <br> 为行界） */
function splitBlock(el: Element): Unit[] {
  const units: Unit[] = []
  let current: ChildNode[] = []
  for (const node of Array.from(el.childNodes)) {
    if (node.nodeType === 1 && (node as Element).tagName === 'BR') {
      if (current.length > 0) units.push(makeUnit(current))
      current = []
    } else {
      current.push(node)
    }
  }
  if (current.length > 0) units.push(makeUnit(current))
  return units
}

function makeUnit(nodes: ChildNode[]): Unit {
  return { nodes, markerOpen: null, markerClose: false, group: null }
}

/** 复制源块的外观属性到重建的 <p> */
function buildParagraph(src: Element, segments: ChildNode[][]): HTMLParagraphElement {
  const p = src.ownerDocument.createElement('p')
  p.className = src.className
  segments.forEach((seg, i) => {
    if (i > 0) p.appendChild(src.ownerDocument.createElement('br'))
    for (const node of seg) p.appendChild(node)
  })
  return p
}

/** 把分组 div 放入文档流：父分组 div 已就位则嵌入其中，否则追加到本块的顶层序列 */
function place(group: Group, sequence: Node[]): void {
  if (group.placed) return
  if (group.parent) {
    place(group.parent, sequence)
    group.parent.div.appendChild(group.div)
  } else {
    sequence.push(group.div)
  }
  group.placed = true
}

/** 属性应用（与后端 FencedDivParser::toHtmlAttributes 同一白名单：class/id/title/style） */
function applyDivAttrs(div: HTMLDivElement, attrs: PandocImageAttrs): void {
  if (attrs.classes.length > 0) div.className = attrs.classes.join(' ')
  if (attrs.id !== undefined && attrs.id !== '') div.id = attrs.id
  if (attrs.title !== undefined && attrs.title !== '') div.title = attrs.title
  const decls: string[] = []
  for (const raw of attrs.rawStyle) {
    for (const decl of raw.split(';')) if (decl.trim()) decls.push(decl.trim())
  }
  decls.push(...attrs.styleDecls)
  for (const decl of decls) {
    const idx = decl.indexOf(':')
    if (idx > 0) div.style.setProperty(decl.slice(0, idx).trim().toLowerCase(), decl.slice(idx + 1).trim())
  }
}

/**
 * 扫描内容区，把 `::: {…} … :::` 围栏重组为 div 包裹（预览只读，安全改写）。
 *
 * @param root 内容根（其直接子块为段落/标题/列表等块级元素）
 */
export function applyPandocFencedDivs(root: ParentNode): void {
  const doc = root.ownerDocument
  if (!doc) return

  // 内容区直接子级存在裸文本节点时结构不可预期（Lute 输出不会有），保守放弃
  for (const node of Array.from(root.childNodes)) {
    if (node.nodeType === 3 && (node.textContent ?? '').trim() !== '') return
  }

  // ── 1. 切块/切行段 ──
  const blocks: Block[] = []
  for (const el of Array.from(root.children)) {
    const units = splitBlock(el)
    blocks.push({ el, units, hasMarker: false })
  }

  // ── 2. 栈式配对（对齐后端：开栏须带合法属性；游离闭栏降级为字面量内容） ──
  const stack: Group[] = []
  for (const block of blocks) {
    for (const unit of block.units) {
      const kind = classifySegment(unit.nodes)
      const isClosing =
        kind !== null &&
        kind.closeLength > 0 &&
        stack.length > 0 &&
        kind.closeLength >= stack[stack.length - 1].fenceLength
      if (kind?.open) {
        const parent = stack.length > 0 ? stack[stack.length - 1] : null
        const div = doc.createElement('div')
        applyDivAttrs(div, kind.open.attrs)
        const group: Group = { div, fenceLength: kind.open.fenceLength, parent, placed: false }
        unit.markerOpen = group
        block.hasMarker = true
        stack.push(group)
      } else if (isClosing && kind) {
        unit.markerClose = true
        block.hasMarker = true
        stack.pop()
      } else if (stack.length > 0) {
        // 普通内容、游离闭栏、冒号数不足的闭栏：归入当前分组（对齐后端字面量语义）
        unit.group = stack[stack.length - 1]
      }
    }
  }

  // ── 3. 重组含标记的块 ──
  for (const block of blocks) {
    if (!block.hasMarker) continue

    const sequence: Node[] = []
    let runSegments: ChildNode[][] = []
    let runGroup: Group | null = null
    const flush = () => {
      if (runSegments.length === 0) return
      const p = buildParagraph(block.el, runSegments)
      if (runGroup) {
        place(runGroup, sequence)
        runGroup.div.appendChild(p)
      } else {
        sequence.push(p)
      }
      runSegments = []
    }

    for (const unit of block.units) {
      if (unit.markerOpen) {
        flush()
        place(unit.markerOpen, sequence)
        continue
      }
      if (unit.markerClose) {
        flush()
        continue
      }
      if (runGroup !== unit.group) {
        flush()
        runGroup = unit.group
      }
      runSegments.push(unit.nodes)
    }
    flush()

    const parent = block.el.parentNode
    if (parent) {
      for (const node of sequence) parent.insertBefore(node, block.el)
      parent.removeChild(block.el)
    }
  }

  // ── 4. 无标记的成员块整体搬入所属 div（顺序按文档序追加） ──
  for (const block of blocks) {
    if (block.hasMarker) continue
    const group = block.units[0]?.group ?? null
    if (!group) continue
    if (!group.placed) {
      // 防御分支：开栏必然经过阶段 3 的 place()，万一未就位则在首个成员前就地插入
      block.el.parentNode?.insertBefore(group.div, block.el)
      group.placed = true
    }
    group.div.appendChild(block.el)
  }
}
