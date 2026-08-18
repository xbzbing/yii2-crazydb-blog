import { useEffect, useState } from 'react'
import { Card, Button, Input, Space, message, Spin, Typography } from 'antd'
import { api } from '../api/client'
import { ADMIN_API_BASE } from '../config'

const { Text, Paragraph } = Typography

/** OTP 二次验证配置区块 */
export default function OtpSettings() {
  const [loading, setLoading] = useState(true)
  const [otpEnabled, setOtpEnabled] = useState(false)
  const [showSetup, setShowSetup] = useState(false)
  const [secret, setSecret] = useState('')
  const [otpCode, setOtpCode] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    api.otpStatus()
      .then((data) => setOtpEnabled(data.otp_enabled))
      .catch((e) => message.error(e.message))
      .finally(() => setLoading(false))
  }, [])

  const handleSetup = async () => {
    try {
      setSubmitting(true)
      const data = await api.otpSetup()
      setSecret(data.secret)
      setShowSetup(true)
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  const handleEnable = async () => {
    if (!otpCode || otpCode.length !== 6) {
      message.warning('请输入 6 位验证码')
      return
    }
    try {
      setSubmitting(true)
      const data = await api.otpEnable(otpCode)
      message.success(data.message || 'OTP 已启用')
      setOtpEnabled(true)
      setShowSetup(false)
      setSecret('')
      setOtpCode('')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  const handleDisable = async () => {
    if (!otpCode || otpCode.length !== 6) {
      message.warning('请输入当前 6 位验证码')
      return
    }
    try {
      setSubmitting(true)
      const data = await api.otpDisable(otpCode)
      message.success(data.message || 'OTP 已关闭')
      setOtpEnabled(false)
      setOtpCode('')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) return <Spin />

  return (
    <Card title="二次验证（OTP）">
      <p style={{ color: '#666', marginBottom: 16 }}>
        为<strong>当前账号</strong>开启二次验证后，登录时除密码外还需输入 Authenticator App（如 Google Authenticator）生成的 6 位验证码。此绑定仅对当前账号生效，不影响其他账号。
      </p>

      {!otpEnabled && !showSetup && (
        <Button type="primary" onClick={handleSetup} loading={submitting}>
          开启 OTP 二次验证
        </Button>
      )}

      {showSetup && (
        <div>
          <Paragraph type="secondary">
            使用 Authenticator App 扫描下方二维码，然后输入 App 生成的验证码确认：
          </Paragraph>
          <img
            src={`${ADMIN_API_BASE}/otp/qr`}
            alt="OTP QR Code"
            style={{ borderRadius: 8, border: '1px solid #eee' }}
          />
          {secret && (
            <div style={{ marginTop: 8 }}>
              <Text type="secondary">手动输入密钥：</Text>
              <Text code copyable>{secret}</Text>
            </div>
          )}
          <Space direction="vertical" style={{ marginTop: 16, width: '100%' }}>
            <Input
              placeholder="输入 6 位验证码"
              maxLength={6}
              value={otpCode}
              onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ''))}
              style={{ width: 200 }}
            />
            <Button type="primary" onClick={handleEnable} loading={submitting} disabled={otpCode.length !== 6}>
              确认启用
            </Button>
          </Space>
        </div>
      )}

      {otpEnabled && !showSetup && (
        <div>
          <Paragraph type="success">OTP 二次验证已启用</Paragraph>
          <Space direction="vertical" style={{ width: '100%' }}>
            <Input
              placeholder="输入 6 位验证码以关闭 OTP"
              maxLength={6}
              value={otpCode}
              onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ''))}
              style={{ width: 200 }}
            />
            <Button danger onClick={handleDisable} loading={submitting} disabled={otpCode.length !== 6}>
              关闭 OTP
            </Button>
          </Space>
        </div>
      )}
    </Card>
  )
}
