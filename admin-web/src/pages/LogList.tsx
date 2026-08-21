import { useRef, useState } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, message, Tooltip, Tag, Modal, Descriptions } from 'antd'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { LogItem } from '../types/api'

export default function LogList() {
  const actionRef = useRef<ActionType>(null)
  const [detailLog, setDetailLog] = useState<LogItem | null>(null)
  const [typeEnum, setTypeEnum] = useState<Record<string, { text: string }>>({})

  const handleClear = async () => {
    try {
      await api.logClear()
      message.success('已清理 1 年前的日志。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const renderUser = (r: LogItem) =>
    r.uid === 0 ? (
      <span style={{ color: 'rgba(0,0,0,0.45)' }}>游客</span>
    ) : (
      <Tooltip title={`ID: ${r.uid}`}>
        <span>{r.nickname || `用户 ${r.uid}`}</span>
      </Tooltip>
    )

  const renderTime = (r: LogItem) =>
    r.create_time ? dayjs.unix(r.create_time).format('YYYY-MM-DD HH:mm') : '-'

  const columns: ProColumns<LogItem>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      width: 90,
      search: false,
      render: (_, r) => (
        <Button type="link" size="small" style={{ padding: 0 }} onClick={() => setDetailLog(r)}>
          {r.id}
        </Button>
      ),
    },
    { title: '类型', dataIndex: 'type', width: 90, valueType: 'select', valueEnum: typeEnum },
    {
      title: '用户',
      dataIndex: 'nickname',
      width: 160,
      search: false,
      render: (_, r) => renderUser(r),
    },
    { title: '动作', dataIndex: 'action', width: 180, ellipsis: true, search: false },
    {
      title: '结果',
      dataIndex: 'result',
      width: 80,
      valueEnum: { success: { text: '成功' }, failed: { text: '失败' } },
      render: (_, r) =>
        r.result === 'success' ? <Tag color="success">成功</Tag> : <Tag color="error">失败</Tag>,
    },
    { title: '详情', dataIndex: 'detail', search: false },
    {
      title: 'IP',
      dataIndex: 'ip',
      width: 130,
      render: (_, r) => (r.ip ? <span>{r.ip}</span> : <span style={{ color: '#bbb' }}>-</span>),
    },
    {
      title: 'User-Agent',
      dataIndex: 'user_agent',
      width: 200,
      ellipsis: true,
      render: (_, r) =>
        r.user_agent ? (
          <Tooltip title={r.user_agent}>
            <span>{r.user_agent}</span>
          </Tooltip>
        ) : (
          <span style={{ color: '#bbb' }}>-</span>
        ),
    },
    {
      title: '时间',
      dataIndex: 'create_time',
      width: 170,
      search: false,
      render: (_, r) => renderTime(r),
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    const res = await api.logs({
      page: params.current || 1,
      type: (params.type as string) || '',
      result: (params.result as string) || '',
      ip: (params.ip as string) || '',
      user_agent: (params.user_agent as string) || '',
    })
    setTypeEnum(Object.fromEntries((res.types ?? []).map((t) => [t, { text: t }])))
    return { data: res.items, total: res.total, success: true }
  }

  return (
    <>
      <ProTable
        headerTitle="日志列表"
        actionRef={actionRef}
        rowKey="id"
        columns={columns}
        request={request}
        pagination={{ defaultPageSize: 20, showSizeChanger: false }}
        search={{ labelWidth: 'auto', span: 8 }}
        options={{ reload: true, density: true, fullScreen: true, setting: true }}
        columnsState={{
          persistenceKey: 'admin-log-list',
          defaultValue: {
            // 默认显示 IP，不显示完整 User-Agent（最小披露）；管理员可在列设置中开启
            ip: { show: true },
            user_agent: { show: false },
          },
        }}
        toolBarRender={() => [
          <Popconfirm key="clear" title="确认清理 1 年前的日志？" onConfirm={handleClear}>
            <Button danger>清理一年前日志</Button>
          </Popconfirm>,
        ]}
      />
      <Modal
        title={`日志详情 #${detailLog?.id ?? ''}`}
        open={detailLog !== null}
        onCancel={() => setDetailLog(null)}
        footer={null}
        centered
        width={624}
        destroyOnClose
      >
        {detailLog && (
          <Descriptions column={1} bordered size="small">
            <Descriptions.Item label="ID">{detailLog.id}</Descriptions.Item>
            <Descriptions.Item label="类型">{detailLog.type}</Descriptions.Item>
            <Descriptions.Item label="用户">{renderUser(detailLog)}</Descriptions.Item>
            <Descriptions.Item label="动作">{detailLog.action}</Descriptions.Item>
            <Descriptions.Item label="结果">
              {detailLog.result === 'success' ? (
                <Tag color="success">成功</Tag>
              ) : (
                <Tag color="error">失败</Tag>
              )}
            </Descriptions.Item>
            <Descriptions.Item label="详情">{detailLog.detail || '-'}</Descriptions.Item>
            <Descriptions.Item label="IP">{detailLog.ip || '-'}</Descriptions.Item>
            <Descriptions.Item label="User-Agent">{detailLog.user_agent || '-'}</Descriptions.Item>
            <Descriptions.Item label="时间">{renderTime(detailLog)}</Descriptions.Item>
          </Descriptions>
        )}
      </Modal>
    </>
  )
}
