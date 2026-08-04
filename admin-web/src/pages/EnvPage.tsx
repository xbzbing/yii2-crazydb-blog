import { useEffect, useState } from 'react'
import { Card, Descriptions, Tag, Spin, message, Progress, Row, Col, Divider } from 'antd'
import { api } from '../api/client'
import type { EnvData } from '../types/api'

/**
 * 系统负载配色：按 负载/CPU核数 比值分档。
 * - ≤70%  绿色（正常）
 * - 70%~100%  黄色（偏高，需关注）
 * - >100%  红色（过载，跑满核）
 */
function loadColor(load: number, cores: number | null | undefined) {
  const ratio = load / (cores || 1)
  if (ratio <= 0.7) return 'green'
  if (ratio <= 1) return 'orange'
  return 'red'
}

function LoadCell({ load, cores }: { load: number; cores: number | null }) {
  return <Tag color={loadColor(load, cores)}>{load}</Tag>
}

export default function EnvPage() {
  const [data, setData] = useState<EnvData | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api
      .env()
      .then(setData)
      .catch((e) => message.error(e instanceof Error ? e.message : String(e)))
      .finally(() => setLoading(false))
  }, [])

  if (loading || !data) return <Spin style={{ margin: 48 }} />

  const { system, php, database, vps } = data

  const memPct = vps?.memory?.usagePercent ?? null
  const diskPct = vps?.disk?.usagePercent ?? null

  return (
    <Card title="环境信息">
      {/* 系统信息 */}
      <Divider orientation="left">系统信息</Divider>
      <Descriptions bordered column={2} size="small">
        <Descriptions.Item label="操作系统软件">
          {system.os} - {system.serverSoftware}
        </Descriptions.Item>
        <Descriptions.Item label="主机名">
          {system.hostname || system.serverName}
        </Descriptions.Item>
        <Descriptions.Item label="数据库及大小">
          {database.version}（{database.size}，{database.tableCount} 张表）
        </Descriptions.Item>
        <Descriptions.Item label="上传许可">
          {php.uploadAllowed ? (
            <Tag color="green">允许（最大 {php.uploadMaxSize}）</Tag>
          ) : (
            <Tag color="red">禁止上传</Tag>
          )}
        </Descriptions.Item>
        <Descriptions.Item label="当前使用内存">
          {php.currentMemoryUsage}
        </Descriptions.Item>
        <Descriptions.Item label="PHP 环境">
          <p style={{ margin: 0 }}>版本：{php.version}（{php.sapi}）</p>
          <p style={{ margin: 0 }}>memory_limit：{php.memoryLimit}</p>
          <p style={{ margin: 0 }}>max_execution_time：{php.maxExecutionTime}</p>
          <p style={{ margin: 0 }}>post_max_size：{php.postMaxSize}</p>
        </Descriptions.Item>
      </Descriptions>

      {/* VPS 运行状态 */}
      <Divider orientation="left">VPS 运行状态</Divider>
      <Descriptions bordered column={2} size="small">
        <Descriptions.Item label="系统内核">{vps.uname || '-'}</Descriptions.Item>
        <Descriptions.Item label="CPU 核心数">{vps.cpuCores ?? '-'} 核</Descriptions.Item>
        <Descriptions.Item label="系统负载">
          {vps.load ? (
            <span>
              1 分钟：<LoadCell load={vps.load['1min']} cores={vps.cpuCores} />{' '}
              5 分钟：<LoadCell load={vps.load['5min']} cores={vps.cpuCores} />{' '}
              15 分钟：<LoadCell load={vps.load['15min']} cores={vps.cpuCores} />
            </span>
          ) : (
            '-'
          )}
        </Descriptions.Item>
        <Descriptions.Item label="运行时长">{vps.uptime || '-'}</Descriptions.Item>
        <Descriptions.Item label="进程数">{vps.processes ?? '-'}</Descriptions.Item>
        <Descriptions.Item label="PHP-FPM 进程">{vps.phpProcesses ?? '-'}</Descriptions.Item>
      </Descriptions>

      {memPct !== null && (
        <Row gutter={[16, 16]} style={{ marginTop: 24 }}>
          <Col span={12}>
            <Card size="small" title="内存使用">
              <Progress
                percent={Number(memPct)}
                status={memPct > 90 ? 'exception' : memPct > 75 ? 'active' : 'normal'}
                format={(p) => `${p}%`}
              />
              <p style={{ marginTop: 8, color: '#888' }}>
                已用 {vps.memory?.used} / 总计 {vps.memory?.total}（可用 {vps.memory?.free}）
              </p>
              <p style={{ color: '#888' }}>Swap：{vps.memory?.swapTotal}（剩余 {vps.memory?.swapFree}）</p>
            </Card>
          </Col>
          <Col span={12}>
            <Card size="small" title="磁盘使用（/）">
              <Progress
                percent={Number(diskPct)}
                status={(diskPct ?? 0) > 90 ? 'exception' : (diskPct ?? 0) > 75 ? 'active' : 'normal'}
                format={(p) => `${p}%`}
              />
              <p style={{ marginTop: 8, color: '#888' }}>
                已用 {vps.disk?.used} / 总计 {vps.disk?.total}（剩余 {vps.disk?.free}）
              </p>
            </Card>
          </Col>
        </Row>
      )}

      {/* PHP 扩展 */}
      <Divider orientation="left">PHP 扩展</Divider>
      <div>
        {php.extensions.split(', ').map((ext) => (
          <Tag key={ext} style={{ marginBottom: 4 }}>
            {ext}
          </Tag>
        ))}
      </div>
    </Card>
  )
}
