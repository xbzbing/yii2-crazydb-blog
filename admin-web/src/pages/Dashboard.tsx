import { useEffect, useState } from 'react'
import { Card, Row, Col, Statistic, Spin, Segmented, Tooltip, Progress } from 'antd'
import { Line } from '@ant-design/plots'
import {
  FileTextOutlined,
  CommentOutlined,
  TeamOutlined,
  DatabaseOutlined,
  EyeOutlined,
  UserOutlined,
  QuestionCircleOutlined,
  BugOutlined,
  CodeOutlined,
  SmileOutlined,
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
    { title: '今日独立IP', value: data.todayIp, icon: <UserOutlined />, color: '#722ed1' },
    { title: '用户总数', value: data.userTotal, icon: <TeamOutlined />, color: '#eb2f96' },
    { title: '配置项', value: data.optionTotal, icon: <DatabaseOutlined />, color: '#13c2c2' },
  ]

  const visitTypes = [
    { title: '正常访问', value: data.todayNormal, icon: <SmileOutlined />, color: '#52c41a' },
    { title: '爬虫访问', value: data.todayCrawler, icon: <BugOutlined />, color: '#fa541c' },
    { title: '脚本访问', value: data.todayScript, icon: <CodeOutlined />, color: '#722ed1' },
  ]

  const chartData = data.visitTrend.flatMap((d) => [
    { date: d.date, value: d.pv, type: 'PV' },
    { date: d.date, value: d.uv, type: 'UV' },
    { date: d.date, value: d.pv_normal, type: '正常' },
    { date: d.date, value: d.pv_crawler, type: '爬虫' },
    { date: d.date, value: d.pv_script, type: '脚本' },
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
          title={
            <>
              今日访问构成
              <Tooltip title="按 UA 关键词判定访问类型（可在「基本设置」配置）">
                <QuestionCircleOutlined style={{ marginLeft: 6, fontSize: 14, color: 'rgba(0,0,0,0.45)', cursor: 'help' }} />
              </Tooltip>
            </>
          }
        >
          <Row gutter={[32, 16]}>
            {visitTypes.map((t) => {
              const percent = data.todayPv > 0 ? Math.round((t.value / data.todayPv) * 100) : 0
              return (
                <Col xs={24} sm={8} key={t.title}>
                  <Statistic
                    title={t.title}
                    value={t.value}
                    suffix={`(${percent}%)`}
                    prefix={<span style={{ color: t.color, marginRight: 8 }}>{t.icon}</span>}
                  />
                  <Progress
                    percent={percent}
                    showInfo={false}
                    strokeColor={t.color}
                    size="small"
                    style={{ marginTop: 4 }}
                  />
                </Col>
              )
            })}
          </Row>
        </Card>
      </Col>

      <Col span={24}>
        <Card
          title={
            <>
              访问趋势
              <Tooltip title="数据每 10 分钟更新一次；分类按 UA 关键词判定（可在「基本设置」配置）">
                <QuestionCircleOutlined style={{ marginLeft: 6, fontSize: 14, color: 'rgba(0,0,0,0.45)', cursor: 'help' }} />
              </Tooltip>
            </>
          }
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
            height={340}
            axis={{ x: { tickCount: Math.min(days, 15), labelFormatter: (v: string) => v.slice(5) } }}
            tooltip={{ channel: 'y', valueFormatter: (v: number) => `${v}` }}
            legend={{ color: { title: false, position: 'top' } }}
            style={{ lineWidth: 2 }}
          />
        </Card>
      </Col>

      {data.visitHourly && data.visitHourly.length > 0 && (
        <Col span={24}>
          <Card title="24 小时趋势（每小时）">
            <Line
              data={data.visitHourly.flatMap((h) => [
                { time: h.time, value: h.pv, type: 'PV' },
                { time: h.time, value: h.uv, type: 'UV' },
                { time: h.time, value: h.ip, type: 'IP' },
              ])}
              xField="time"
              yField="value"
              colorField="type"
              height={280}
              axis={{
                x: {
                  labelFormatter: (v: string) => v.slice(11, 13) + ':00',
                },
              }}
              tooltip={{ channel: 'y', valueFormatter: (v: number) => `${v}` }}
              legend={{ color: { title: false, position: 'top' } }}
              style={{ lineWidth: 2 }}
            />
          </Card>
        </Col>
      )}
    </Row>
  )
}
