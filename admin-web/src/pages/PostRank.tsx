import { useEffect, useState, useCallback } from 'react'
import { Tabs, Table, Spin, Button, Alert, Empty, Tag, Space, Tooltip } from 'antd'
import { ReloadOutlined, InfoCircleOutlined, EditOutlined, ExportOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import type { RankItem } from '../api/client'

type Day = 'today' | 'yesterday'

interface TabState {
  data: RankItem[] | null
  loading: boolean
  error: string | null
  date: string
}

export default function PostRank() {
  const navigate = useNavigate()
  const [activeTab, setActiveTab] = useState<Day>('today')
  const columns = [
    {
      title: '排名',
      key: 'rank',
      width: 72,
      render: (_: unknown, __: RankItem, index: number) => {
        const rank = index + 1
        const color = rank === 1 ? '#f5222d' : rank === 2 ? '#fa8c16' : rank === 3 ? '#faad14' : undefined
        return (
          <span style={{ fontWeight: 600, color, fontSize: rank <= 3 ? 16 : 14 }}>
            {rank}
          </span>
        )
      },
    },
    {
      title: '文章标题',
      dataIndex: 'title',
      key: 'title',
    },
    {
      title: '阅读次数',
      dataIndex: 'views',
      key: 'views',
      width: 120,
      render: (views: number) => <Tag color="blue">{views}</Tag>,
    },
    {
      title: '操作',
      key: 'actions',
      width: 130,
      render: (_: unknown, record: RankItem) => (
        <Space.Compact>
          {record.alias && (
            <Tooltip title="前台访问">
              <Button
                size="small"
                icon={<ExportOutlined />}
                onClick={() => window.open(`/archive/${record.alias}`, '_blank')}
              />
            </Tooltip>
          )}
          <Tooltip title="编辑">
            <Button
              size="small"
              icon={<EditOutlined />}
              onClick={() => navigate(`/posts/${record.post_id}/edit`)}
            />
          </Tooltip>
        </Space.Compact>
      ),
    },
  ]
  const [tabs, setTabs] = useState<Record<Day, TabState>>({
    today: { data: null, loading: false, error: null, date: '' },
    yesterday: { data: null, loading: false, error: null, date: '' },
  })

  const fetchData = useCallback(
    (day: Day, signal?: AbortSignal) => {
      setTabs((prev) => ({
        ...prev,
        [day]: { ...prev[day], loading: true, error: null },
      }))

      api
        .postRank(day, signal)
        .then((res) => {
          setTabs((prev) => ({
            ...prev,
            [day]: { data: res.items, loading: false, error: null, date: res.date },
          }))
        })
        .catch((e: unknown) => {
          if (e instanceof DOMException && e.name === 'AbortError') return
          setTabs((prev) => ({
            ...prev,
            [day]: { ...prev[day], loading: false, error: '加载失败，请重试。' },
          }))
        })
    },
    [],
  )

  // 首次加载 & tab 切换时请求
  useEffect(() => {
    const controller = new AbortController()
    fetchData(activeTab, controller.signal)
    return () => controller.abort()
  }, [activeTab, fetchData])

  const renderTab = (day: Day) => {
    const state = tabs[day]

    if (state.error && !state.data) {
      return (
        <div style={{ textAlign: 'center', padding: 48 }}>
          <p style={{ color: 'rgba(0,0,0,0.45)', marginBottom: 16 }}>{state.error}</p>
          <Button type="primary" icon={<ReloadOutlined />} onClick={() => fetchData(day)}>
            重试
          </Button>
        </div>
      )
    }

    if (state.loading && !state.data) return <Spin style={{ margin: 48, display: 'block' }} />

    if (state.data && state.data.length === 0) {
      return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="该日暂无阅读数据" style={{ margin: 48 }} />
    }

    return (
      <Table<RankItem>
        rowKey="post_id"
        columns={columns}
        dataSource={state.data ?? []}
        pagination={false}
        loading={state.loading}
        size="middle"
      />
    )
  }

  const items = [
    {
      key: 'today',
      label: '今日',
      children: renderTab('today'),
    },
    {
      key: 'yesterday',
      label: '昨日',
      children: renderTab('yesterday'),
    },
  ]

  return (
    <div>
      <Alert
        message={
          <span>
            <InfoCircleOutlined style={{ marginRight: 6 }} />
            数据来自缓存（Redis 按日计数），可能存在偏差
          </span>
        }
        type="info"
        showIcon={false}
        style={{ marginBottom: 16 }}
      />
      <Tabs activeKey={activeTab} onChange={(key) => setActiveTab(key as Day)} items={items} />
    </div>
  )
}
