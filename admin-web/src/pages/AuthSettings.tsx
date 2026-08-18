import { Card, Alert, Typography } from 'antd'
import OtpSettings from './OtpSettings'
import { usePageTitle } from '../contexts/PageTitleContext'

const { Paragraph } = Typography

/**
 * 站点配置 - 认证配置
 *
 * 账号安全相关设置（如 OTP 二次验证）属于「当前登录账号」的绑定，
 * 而非站点全局配置：这里的所有操作只影响当前管理员账号，不影响其他用户。
 */
export default function AuthSettings() {
  usePageTitle('认证配置')

  return (
    <div>
      <Card title="认证配置" style={{ marginBottom: 16 }}>
        <Alert
          type="info"
          showIcon
          message="仅影响当前账号"
          description="以下设置只对当前登录的管理员账号生效（按用户绑定），不会影响其他账号的登录方式。"
          style={{ marginBottom: 16 }}
        />
        <Paragraph type="secondary" style={{ marginBottom: 0 }}>
          账号安全设置，包括登录二次验证（OTP）。建议为管理员账号开启二次验证，提升密码泄露场景下的账户安全。
        </Paragraph>
      </Card>

      <OtpSettings />
    </div>
  )
}
