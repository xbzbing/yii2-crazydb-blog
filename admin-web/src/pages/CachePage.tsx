import { useEffect, useState } from 'react'
import { Card, Descriptions, Button, Popconfirm, message, Spin, Tag, Progress, Statistic, Row, Col } from 'antd'
import { ClearOutlined, SyncOutlined } from '@ant-design/icons'
import { api } from '../api/client'
import type { CacheStatus } from '../types/api'

function formatBytes(bytes: number | undefined) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let n = Number(bytes)
  let i = 0
  while (n >= 1024 && i < units.length - 1) {
    n /= 1024
    i++
  }
  return n.toFixed(2) + ' ' + units[i]
}

function formatUptime(sec: number | undefined) {
  if (!sec) return '-'
  const d = Math.floor(sec / 86400)
  const h = Math.floor((sec % 86400) / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const parts = []
  if (d > 0) parts.push(d + ' 天')
  if (h > 0) parts.push(h + ' 小时')
  parts.push(m + ' 分钟')
  return parts.join(' ')
}

export default function CachePage() {
  const [data, setData] = useState<CacheStatus | null>(null)
  const [, setLoading] = useState(true)

  const load = () => {
    setLoading(true)
    api
      .cacheStatus()
      .then(setData)
      .catch((e) => message.error(e instanceof Error ? e.message : String(e)))
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const handleClear = async () => {
    try {
      await api.cacheClear()
      message.success('缓存已清空。')
      load()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  if (!data) return <Spin style={{ margin: 48 }} />

  if (data && !data.connected) {
    return (
      <Card title="缓存管理">
        <Tag color="red">Redis 连接失败</Tag>
        <pre style={{ marginTop: 16 }}>{data.error}</pre>
        <Button icon={<SyncOutlined />} onClick={load}>
          重试
        </Button>
      </Card>
    )
  }

  const hits = data.hits ?? 0
  const misses = data.misses ?? 0
  const maxMemory = data.maxMemory ?? 0
  const usedMemory = data.usedMemory ?? 0
  const hitRate = hits + misses > 0 ? ((hits / (hits + misses)) * 100).toFixed(1) : '0.0'
  const memUsage = maxMemory > 0 ? Math.min(100, Number(((usedMemory / maxMemory) * 100).toFixed(1))) : 0

  return (
    <Card
      title="缓存管理"
      extra={
        <Popconfirm title="确认清空全部应用缓存？" description="可能影响系统稳定，建议避开高峰期。" onConfirm={handleClear}>
          <Button type="primary" danger icon={<ClearOutlined />}>
            清空所有缓存
          </Button>
        </Popconfirm>
      }
    >
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col span={6}>
          <Card size="small">
            <Statistic title="缓存键数量" value={data.totalKeys} />
          </Card>
        </Col>
        <Col span={6}>
          <Card size="small">
            <Statistic title="已用内存" value={formatBytes(data.usedMemory)} />
          </Card>
        </Col>
        <Col span={6}>
          <Card size="small">
            <Statistic title="命中率" value={hitRate} suffix="%" />
          </Card>
        </Col>
        <Col span={6}>
          <Card size="small">
            <Statistic title="连接数" value={data.connectedClients} />
          </Card>
        </Col>
      </Row>

      {maxMemory > 0 && (
        <Progress
          percent={Number(memUsage)}
          status={memUsage > 90 ? 'exception' : 'active'}
          format={(p) => `${p}%（最大 ${formatBytes(data.maxMemory)}）`}
          style={{ marginBottom: 24 }}
        />
      )}

      <Descriptions bordered column={2} size="small">
        <Descriptions.Item label="Redis 版本">{data.redisVersion}</Descriptions.Item>
        <Descriptions.Item label="数据库">{data.db}</Descriptions.Item>
        <Descriptions.Item label="运行时长">{formatUptime(data.uptime)}</Descriptions.Item>
        <Descriptions.Item label="峰值内存">{formatBytes(data.usedMemoryPeak)}</Descriptions.Item>
        <Descriptions.Item label="命中次数">{data.hits}</Descriptions.Item>
        <Descriptions.Item label="未命中次数">{data.misses}</Descriptions.Item>
      </Descriptions>
    </Card>
  )
}
