import { useEffect, useState } from 'react'
import { Card, Row, Col, Statistic, Spin, Segmented, Tooltip, Progress, Button, Empty } from 'antd'
import { Line } from '@ant-design/plots'
import {
  FileTextOutlined,
  CommentOutlined,
  TeamOutlined,
  DatabaseOutlined,
  EyeOutlined,
  UserOutlined,
  GlobalOutlined,
  QuestionCircleOutlined,
  BugOutlined,
  CodeOutlined,
  SmileOutlined,
  WarningOutlined,
} from '@ant-design/icons'
import { api } from '../api/client'
import type { DashboardData } from '../types/api'

export default function Dashboard() {
  const [data, setData] = useState<DashboardData | null>(null)
  const [days, setDays] = useState(14)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    setLoading(true)
    setError(null)
    setData(null)
    api
      .dashboard(days, controller.signal)
      .then(setData)
      .catch((e: unknown) => {
        // 切换 days 时旧请求被 abort，忽略其错误
        if (e instanceof DOMException && e.name === 'AbortError') return
        setError('加载失败，请重试。')
      })
      .finally(() => {
        // 仅最新请求可以结束 loading（旧请求 abort 后不再改状态）
        if (!controller.signal.aborted) setLoading(false)
      })
    return () => controller.abort()
  }, [days, reloadKey])

  if (error && !data) {
    return (
      <div style={{ textAlign: 'center', padding: 48 }}>
        <p style={{ color: 'rgba(0,0,0,0.45)', marginBottom: 16 }}>{error}</p>
        <Button type="primary" onClick={() => setReloadKey((k) => k + 1)}>
          重试
        </Button>
      </div>
    )
  }

  if (loading || !data) return <Spin style={{ margin: 48 }} />

  const hasHourlyData = data.visitHourly?.some((h) => h.pv > 0 || h.uv > 0 || h.ip > 0) ?? false

  // 与昨天相比的涨跌标记：
  // - 昨日有数据 → 涨跌百分比
  // - 昨日为 0、今日有数据 → 「↑ 新」
  // - 两日都为 0 → 「—」（无对比基准）
  type DiffState =
    | { kind: 'percent'; value: number }
    | { kind: 'new' }
    | { kind: 'flat-none' }

  const vsYesterday = (today: number, yesterday: number): DiffState => {
    if (yesterday > 0) {
      return { kind: 'percent', value: Math.round(((today - yesterday) / yesterday) * 100) }
    }
    return today > 0 ? { kind: 'new' } : { kind: 'flat-none' }
  }

  interface StatItem {
    title: string
    value: number
    icon: React.ReactNode
    color: string
    /** 与昨日相比的涨跌状态；undefined = 不显示（文章总数等非统计卡） */
    diff?: DiffState
  }

  const stats: StatItem[] = [
    { title: '文章总数', value: data.postTotal, icon: <FileTextOutlined />, color: '#1677ff' },
    { title: '评论总数', value: data.commentTotal, icon: <CommentOutlined />, color: '#52c41a' },
    { title: '用户总数', value: data.userTotal, icon: <TeamOutlined />, color: '#eb2f96' },
    { title: '配置项', value: data.optionTotal, icon: <DatabaseOutlined />, color: '#8c8c8c' },
    { title: '今日访问', value: data.todayPv, icon: <EyeOutlined />, color: '#fa8c16', diff: vsYesterday(data.todayPv, data.yesterdayPv ?? 0) },
    { title: '今日独立IP', value: data.todayIp, icon: <GlobalOutlined />, color: '#722ed1', diff: vsYesterday(data.todayIp, data.yesterdayIp ?? 0) },
    { title: '今日 UV', value: data.todayUv, icon: <UserOutlined />, color: '#13c2c2', diff: vsYesterday(data.todayUv, data.yesterdayUv ?? 0) },
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
              suffix={
                s.diff === undefined ? undefined : (
                  s.diff.kind === 'percent' ? (
                    s.diff.value === 0 ? (
                      <span style={{ fontSize: 13, fontWeight: 500, color: '#8c8c8c', marginLeft: 4 }}>
                        持平
                      </span>
                    ) : (
                      <span
                        style={{
                          fontSize: 13,
                          fontWeight: 500,
                          color: s.diff.value > 0 ? '#cf1322' : '#389e0d',
                          marginLeft: 4,
                        }}
                      >
                        {s.diff.value > 0 ? '↑' : '↓'}
                        {Math.abs(s.diff.value)}%
                      </span>
                    )
                  ) : s.diff.kind === 'new' ? (
                    <span style={{ fontSize: 13, fontWeight: 500, color: '#cf1322', marginLeft: 4 }}>
                      ↑ 新
                    </span>
                  ) : (
                    <span style={{ fontSize: 13, fontWeight: 500, color: '#8c8c8c', marginLeft: 4 }}>
                      —
                    </span>
                  )
                )
              }
            />
          </Card>
        </Col>
      ))}

      {/* 404 探测监控卡：404 不进主 PV/UV，独立计数用于监控疑似扫描器 */}
      <Col xs={24} sm={12} lg={8} xl={6}>
        <Card>
          <Statistic
            title={
              <>
                今日 404 探测
                <Tooltip title="404 请求不计入 PV/UV；独立统计用于监控疑似扫描器">
                  <QuestionCircleOutlined style={{ marginLeft: 6, fontSize: 14, color: 'rgba(0,0,0,0.45)', cursor: 'help' }} />
                </Tooltip>
              </>
            }
            value={data.todayNotFoundPv}
            prefix={<span style={{ color: '#fa541c', marginRight: 8 }}><WarningOutlined /></span>}
            suffix={
              (() => {
                const diff = vsYesterday(data.todayNotFoundPv, data.yesterdayNotFoundPv ?? 0)
                return (
                  <span style={{ fontSize: 13, fontWeight: 500, marginLeft: 4 }}>
                    <span style={{ color: 'rgba(0,0,0,0.45)', marginRight: 6 }}>来源 IP {data.todayNotFoundUv}</span>
                    {diff.kind === 'percent' ? (
                      diff.value === 0 ? (
                        <span style={{ color: '#8c8c8c' }}>持平</span>
                      ) : (
                        <span style={{ color: diff.value > 0 ? '#cf1322' : '#389e0d' }}>
                          {diff.value > 0 ? '↑' : '↓'}
                          {Math.abs(diff.value)}%
                        </span>
                      )
                    ) : diff.kind === 'new' ? (
                      <span style={{ color: '#cf1322' }}>↑ 新</span>
                    ) : (
                      <span style={{ color: '#8c8c8c' }}>—</span>
                    )}
                  </span>
                )
              })()
            }
          />
        </Card>
      </Col>

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
            {hasHourlyData ? (
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
            ) : (
              <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="暂无访问数据" />
            )}
          </Card>
        </Col>
      )}
    </Row>
  )
}
