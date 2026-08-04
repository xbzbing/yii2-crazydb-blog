import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, message } from 'antd'
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
    { title: '类型', dataIndex: 'type', width: 160, valueType: 'select' },
    { title: '用户', dataIndex: 'uid', width: 80, search: false },
    { title: '动作', dataIndex: 'action', width: 180, ellipsis: true, search: false },
    { title: '结果', dataIndex: 'result', width: 100, search: false },
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
