import { Alert } from 'antd'
import OtpSettings from './OtpSettings'
import { usePageTitle } from '../contexts/PageTitleContext'

/**
 * 站点配置 - 认证配置
 *
 * 账号安全设置（OTP 二次验证等）为当前登录账号的 per-user 绑定，
 * 不属于站点全局配置，故独立成页。
 */
export default function AuthSettings() {
  usePageTitle('认证配置')

  return (
    <div>
      <Alert type="info" showIcon message="以下设置仅对当前登录账号生效，不影响其他账号" style={{ marginBottom: 16 }} />
      <OtpSettings />
    </div>
  )
}
