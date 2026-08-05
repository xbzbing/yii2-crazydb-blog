import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, message, Tooltip, Tag } from 'antd'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { LogItem } from '../types/api'

export default function LogList() {
  const actionRef = useRef<ActionType>(null)

  const handleClear = async () => {
    try {
      await api.logClear()
      message.success('日志已清空。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<LogItem>[] = [
    { title: 'ID', dataIndex: 'id', width: 70, search: false },
    { title: '类型', dataIndex: 'type', width: 130, valueType: 'select' },
    {
      title: '用户',
      dataIndex: 'nickname',
      width: 100,
      search: false,
      render: (_, r) =>
        r.uid === 0 ? (
          <span style={{ color: 'rgba(0,0,0,0.45)' }}>游客</span>
        ) : (
          <Tooltip title={`ID: ${r.uid}`}>
            <span>{r.nickname || `用户 ${r.uid}`}</span>
          </Tooltip>
        ),
    },
    { title: '动作', dataIndex: 'action', width: 180, ellipsis: true, search: false },
    {
      title: '结果',
      dataIndex: 'result',
      width: 80,
      search: false,
      render: (_, r) =>
        r.result === 'success' ? <Tag color="success">成功</Tag> : <Tag color="error">失败</Tag>,
    },
    { title: '详情', dataIndex: 'detail', ellipsis: true, search: false },
    {
      title: '时间',
      dataIndex: 'create_time',
      width: 130,
      search: false,
      render: (_, r) => (r.create_time ? dayjs.unix(r.create_time).format('YYYY-MM-DD HH:mm') : '-'),
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    const res = await api.logs({
      page: params.current || 1,
      type: (params.type as string) || '',
    })
    return { data: res.items, total: res.total, success: true }
  }

  return (
    <ProTable
      headerTitle="日志列表"
      actionRef={actionRef}
      rowKey="id"
      columns={columns}
      request={request}
      pagination={{ defaultPageSize: 20, showSizeChanger: false }}
      toolBarRender={() => [
        <Popconfirm key="clear" title="确认清空全部日志？" onConfirm={handleClear}>
          <Button danger>清空日志</Button>
        </Popconfirm>,
      ]}
    />
  )
}
