import { useEffect, useState } from 'react'
import { Card, Button, Input, Modal, Space, message, Spin, Typography } from 'antd'
import { api } from '../api/client'
import { ADMIN_API_BASE } from '../config'

const { Text, Paragraph } = Typography

/** OTP 二次验证配置（当前账号） */
export default function OtpSettings() {
  const [loading, setLoading] = useState(true)
  const [otpEnabled, setOtpEnabled] = useState(false)
  const [setupOpen, setSetupOpen] = useState(false)
  const [disableOpen, setDisableOpen] = useState(false)
  const [secret, setSecret] = useState('')
  const [otpCode, setOtpCode] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    api
      .otpStatus()
      .then((data) => setOtpEnabled(data.otp_enabled))
      .catch((e) => message.error(e.message))
      .finally(() => setLoading(false))
  }, [])

  const openSetup = async () => {
    try {
      setSubmitting(true)
      const data = await api.otpSetup()
      setSecret(data.secret)
      setOtpCode('')
      setSetupOpen(true)
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  const handleEnable = async () => {
    if (otpCode.length !== 6) {
      message.warning('请输入 6 位验证码')
      return
    }
    try {
      setSubmitting(true)
      const data = await api.otpEnable(otpCode)
      message.success(data.message || 'OTP 已启用')
      setOtpEnabled(true)
      setSetupOpen(false)
      setSecret('')
      setOtpCode('')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  const handleDisable = async () => {
    if (otpCode.length !== 6) {
      message.warning('请输入当前 6 位验证码')
      return
    }
    try {
      setSubmitting(true)
      const data = await api.otpDisable(otpCode)
      message.success(data.message || 'OTP 已关闭')
      setOtpEnabled(false)
      setDisableOpen(false)
      setOtpCode('')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  const openDisable = () => {
    setOtpCode('')
    setDisableOpen(true)
  }

  /** 关闭任一 Modal：清空验证码输入，避免下次打开残留 */
  const closeModal = (which: 'setup' | 'disable') => {
    setOtpCode('')
    if (which === 'setup') setSetupOpen(false)
    else setDisableOpen(false)
  }

  if (loading) return <Spin />

  return (
    <Card title="二次验证（OTP）">
      <Paragraph type="secondary" style={{ marginBottom: 16 }}>
        开启后，当前账号登录时需额外输入 6 位动态验证码。
      </Paragraph>

      {!otpEnabled ? (
        <Button type="primary" onClick={openSetup} loading={submitting}>
          开启 OTP 二次验证
        </Button>
      ) : (
        <Space direction="vertical">
          <Paragraph type="success" strong style={{ margin: 0 }}>
            ✓ 已启用
          </Paragraph>
          <Button danger onClick={openDisable}>
            关闭 OTP
          </Button>
        </Space>
      )}

      {/* 开启流程：扫码绑定 */}
      <Modal
        title="绑定 OTP 二次验证"
        open={setupOpen}
        onCancel={() => closeModal('setup')}
        okText="确认启用"
        cancelText="取消"
        confirmLoading={submitting}
        okButtonProps={{ disabled: otpCode.length !== 6 }}
        onOk={handleEnable}
        destroyOnClose
      >
        <Paragraph type="secondary">
          使用 Authenticator App 扫描下方二维码（或手动输入密钥），然后输入 App 生成的 6 位验证码完成绑定：
        </Paragraph>
        <div style={{ textAlign: 'center', margin: '12px 0' }}>
          <img
            src={`${ADMIN_API_BASE}/otp/qr`}
            alt="OTP QR Code"
            style={{ borderRadius: 8, border: '1px solid #eee', width: 180, height: 180 }}
          />
        </div>
        <div style={{ textAlign: 'center', marginBottom: 12 }}>
          <Text type="secondary">手动输入密钥：</Text>
          <Text code copyable>
            {secret}
          </Text>
        </div>
        <Input
          placeholder="输入 6 位验证码"
          maxLength={6}
          value={otpCode}
          onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ''))}
          style={{ textAlign: 'center', letterSpacing: 4 }}
        />
      </Modal>

      {/* 关闭流程：当前码确认 */}
      <Modal
        title="关闭 OTP 二次验证"
        open={disableOpen}
        onCancel={() => closeModal('disable')}
        okText="确认关闭"
        okButtonProps={{ danger: true, disabled: otpCode.length !== 6 }}
        cancelText="取消"
        confirmLoading={submitting}
        onOk={handleDisable}
        destroyOnClose
      >
        <Paragraph type="secondary" style={{ marginBottom: 12 }}>
          输入当前 6 位验证码以确认关闭。
        </Paragraph>
        <Input
          placeholder="输入 6 位验证码"
          maxLength={6}
          value={otpCode}
          onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ''))}
          style={{ textAlign: 'center', letterSpacing: 4 }}
        />
      </Modal>
    </Card>
  )
}
