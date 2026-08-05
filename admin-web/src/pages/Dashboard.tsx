import { useEffect, useState } from 'react'
import { Card, Row, Col, Statistic, Spin, Segmented } from 'antd'
import { Line } from '@ant-design/plots'
import {
  FileTextOutlined,
  CommentOutlined,
  TeamOutlined,
  DatabaseOutlined,
  EyeOutlined,
  UserOutlined,
} from '@ant-design/icons'
import { api } from '../api/client'
import type { DashboardData } from '../types/api'

export default function Dashboard() {
  const [data, setData] = useState<DashboardData | null>(null)
  const [days, setDays] = useState(14)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    setLoading(true)
    api
      .dashboard(days)
      .then(setData)
      .finally(() => setLoading(false))
  }, [days])

  if (loading || !data) return <Spin style={{ margin: 48 }} />

  const stats = [
    { title: '文章总数', value: data.postTotal, icon: <FileTextOutlined />, color: '#1677ff' },
    { title: '评论总数', value: data.commentTotal, icon: <CommentOutlined />, color: '#52c41a' },
    { title: '今日访问', value: data.todayPv, icon: <EyeOutlined />, color: '#fa8c16' },
    { title: '今日独立IP', value: data.todayUv, icon: <UserOutlined />, color: '#722ed1' },
    { title: '用户总数', value: data.userTotal, icon: <TeamOutlined />, color: '#eb2f96' },
    { title: '配置项', value: data.optionTotal, icon: <DatabaseOutlined />, color: '#13c2c2' },
  ]

  const chartData = data.visitTrend.flatMap((d) => [
    { date: d.date, value: d.pv, type: 'PV' },
    { date: d.date, value: d.uv, type: 'UV' },
  ])

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

      <Col span={24}>
        <Card
          title="访问趋势"
          extra={
            <Segmented
              options={[
                { label: '7 天', value: 7 },
                { label: '14 天', value: 14 },
                { label: '30 天', value: 30 },
              ]}
              value={days}
              onChange={(v) => setDays(Number(v))}
            />
          }
        >
          <Line
            data={chartData}
            xField="date"
            yField="value"
            colorField="type"
            height={320}
            axis={{ x: { tickCount: Math.min(days, 15), labelFormatter: (v: string) => v.slice(5) } }}
            tooltip={{ channel: 'y', valueFormatter: (v: number) => `${v}` }}
            legend={{ color: { title: false, position: 'top' } }}
            style={{ lineWidth: 2 }}
          />
        </Card>
      </Col>
    </Row>
  )
}
