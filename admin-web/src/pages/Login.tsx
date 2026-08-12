import { useState } from 'react'
import { Card, Form, Input, Button, message, Typography, Checkbox, Row, Col } from 'antd'
import { UserOutlined, LockOutlined, SafetyOutlined } from '@ant-design/icons'
import { fetchCsrfToken, getCsrfToken, fetchMe } from '../api/client'
import type { MeData } from '../types/api'

interface LoginProps {
  onLoggedIn: (user: MeData['user']) => void
}

/**
 * 登录页：POST 到前台 /login（服务端表单端点，session 登录 + 写 cookie），
 * 成功后 fetchMe 拉取管理员信息并进入后台。
 * 验证码经 /tool/captcha 展示；dev 环境 CAPTCHA_DEBUG=1 直通。
 */
export default function LoginPage({ onLoggedIn }: LoginProps) {
  const [loading, setLoading] = useState(false)
  const [captchaUrl, setCaptchaUrl] = useState('/tool/captcha?t=' + Date.now())

  const doLogin = async (values: { username: string; password: string; remember?: boolean; captcha?: string }) => {
    setLoading(true)
    try {
      // 1. 拿 CSRF token（未登录时 /me 返回 401，守卫会在包体带 csrf）
      let token = getCsrfToken()
      if (!token) token = await fetchCsrfToken()

      // 2. 提交登录（urlencoded；验证码 dev 直通）
      const params = new URLSearchParams({
        username: values.username,
        password: values.password,
        rememberMe: values.remember ? '1' : '0',
        captcha: values.captcha || '',
        _csrf: token || '',
      })
      const res = await fetch('/login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.status === 302 || res.status === 200) {
        // 服务端登录成功 → 验证管理员态
        const meJson = await fetchMe()
        if (meJson?.user) {
          onLoggedIn(meJson.user)
          window.location.hash = '#/'
          return
        }
        throw new Error('账号无管理员权限。')
      }
      if (res.status === 422) {
        // 可能是 CSRF 失败或验证码错误——重新拉取 token 与验证码
        setCaptchaUrl('/tool/captcha?t=' + Date.now())
        throw new Error('校验失败，请重试（已刷新验证码）。')
      }
      // 失败 → 服务端渲染了错误页，尝试提取 flash 文案
      const text = await res.text().catch(() => '')
      const m = text.match(/用户名和密码不匹配|验证码错误|被禁用|锁定/)
      throw new Error(m?.[0] || '登录失败，请重试。')
    } catch (e) {
      message.error(e instanceof Error ? e.message || '登录失败。' : '登录失败。')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div
      style={{
        height: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #1677ff 0%, #0e5cdb 100%)',
      }}
    >
      <Card style={{ width: 400, borderRadius: 12 }}>
        <div style={{ textAlign: 'center', marginBottom: 24 }}>
          <Typography.Title level={3} style={{ marginBottom: 4 }}>
            Crazydb-Blog 后台管理
          </Typography.Title>
          <Typography.Text type="secondary">请输入管理员账号登录</Typography.Text>
        </div>
        <Form layout="vertical" onFinish={doLogin} requiredMark={false}>
          <Form.Item name="username" rules={[{ required: true, message: '请输入用户名' }]}>
            <Input size="large" prefix={<UserOutlined />} placeholder="用户名" autoFocus />
          </Form.Item>
          <Form.Item name="password" rules={[{ required: true, message: '请输入密码' }]}>
            <Input.Password size="large" prefix={<LockOutlined />} placeholder="密码" />
          </Form.Item>
          <Form.Item label="验证码">
            <Row gutter={8} align="middle">
              <Col flex="1">
                <Form.Item name="captcha" noStyle>
                  <Input size="large" prefix={<SafetyOutlined />} placeholder="验证码" />
                </Form.Item>
              </Col>
              <Col>
                <img
                  src={captchaUrl}
                  alt="验证码"
                  title="点击换图"
                  style={{ height: 40, cursor: 'pointer', borderRadius: 6, verticalAlign: 'middle' }}
                  onClick={() => setCaptchaUrl('/tool/captcha?t=' + Date.now())}
                />
              </Col>
            </Row>
          </Form.Item>
          <Form.Item name="remember" valuePropName="checked" initialValue={true}>
            <Checkbox>记住我</Checkbox>
          </Form.Item>
          <Button type="primary" htmlType="submit" size="large" block loading={loading}>
            登 录
          </Button>
        </Form>
      </Card>
    </div>
  )
}
