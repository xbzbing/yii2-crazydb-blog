import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Popconfirm, Tag, message } from 'antd'
import { api } from '../api/client'
import type { Tag as TagType } from '../types/api'

export default function TagList() {
  const actionRef = useRef<ActionType>(null)

  const handleDelete = async (name: string) => {
    try {
      await api.tagDelete(name)
      message.success('标签已删除。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<TagType>[] = [
    {
      title: '标签',
      dataIndex: 'name',
      render: (_, r) => <Tag color="blue">{r.name}</Tag>,
    },
    { title: '文章数', dataIndex: 'totalCount', width: 120 },
    {
      title: '操作',
      valueType: 'option',
      width: 100,
      render: (_, r) => (
        <Popconfirm title={`确认删除标签「${r.name}」？`} onConfirm={() => handleDelete(r.name)}>
          <a style={{ color: '#ff4d4f' }}>删除</a>
        </Popconfirm>
      ),
    },
  ]

  const request = async () => {
    const res = await api.tags()
    return { data: res.items, total: res.items.length, success: true }
  }

  return (
    <ProTable
      headerTitle="标签列表"
      actionRef={actionRef}
      rowKey="name"
      columns={columns}
      request={request}
      search={false}
      pagination={false}
    />
  )
}
