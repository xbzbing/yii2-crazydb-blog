import { useEffect, useState } from 'react'
import { Card, Row, Col, Statistic, Spin } from 'antd'
import {
  FileTextOutlined,
  CommentOutlined,
  TeamOutlined,
  DatabaseOutlined,
} from '@ant-design/icons'
import { api } from '../api/client'
import type { DashboardData } from '../types/api'

export default function Dashboard() {
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api
      .dashboard()
      .then(setData)
      .finally(() => setLoading(false))
  }, [])

  if (loading || !data) return <Spin style={{ margin: 48 }} />

  const stats = [
    { title: '文章总数', value: data.postTotal, icon: <FileTextOutlined />, color: '#1677ff' },
    { title: '评论总数', value: data.commentTotal, icon: <CommentOutlined />, color: '#52c41a' },
    { title: '待审核评论', value: data.pendingComments, icon: <CommentOutlined />, color: '#faad14' },
    { title: '用户总数', value: data.userTotal, icon: <TeamOutlined />, color: '#722ed1' },
    { title: '配置项', value: data.optionTotal, icon: <DatabaseOutlined />, color: '#13c2c2' },
  ]

  return (
    <Row gutter={[16, 16]}>
      {stats.map((s) => (
        <Col xs={24} sm={12} lg={8} xl={6} key={s.title}>
          <Card>
            <Statistic
              title={s.title}
              value={s.value}
              prefix={<span style={{ color: s.color, marginRight: 8 }}>{s.icon}</span>}
            />
          </Card>
        </Col>
      ))}
    </Row>
  )
}
