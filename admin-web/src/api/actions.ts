import { message } from 'antd'

export const errMessage = (e: unknown) => (e instanceof Error ? e.message : String(e))

/**
 * 统一的「执行 → 成功提示 → 后续动作」模板：
 * 成功才 toast 并执行 afterSuccess；失败只 toast 错误，不抛出。
 */
export async function runAction(
  fn: () => Promise<unknown>,
  successMsg: string,
  afterSuccess?: () => void,
): Promise<void> {
  try {
    await fn()
    message.success(successMsg)
    afterSuccess?.()
  } catch (e) {
    message.error(errMessage(e))
  }
}

/**
 * 保存结果处理：后端业务校验失败返回 { ok: false, errors }（外层 HTTP ok），
 * 需单独拼接 errors 提示；返回 false 表示保存失败，调用方应中止后续流程。
 */
export function reportSave(
  res: { ok?: boolean; errors?: Record<string, string>; message?: string } | undefined,
  successFallback: string,
): boolean {
  if (res && res.ok === false) {
    message.error(Object.values(res.errors || {}).join('；') || '保存失败。')
    return false
  }
  message.success(res?.message || successFallback)
  return true
}
