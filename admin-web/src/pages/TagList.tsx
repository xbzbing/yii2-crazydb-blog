import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, Tag, Space, Tooltip, message } from 'antd'
import { DeleteOutlined, ExportOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import type { Tag as TagType } from '../types/api'

export default function TagList() {
  const navigate = useNavigate()
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
      render: (_, r) => (
        <a onClick={() => navigate(`/posts?tag=${encodeURIComponent(r.name)}`)}>
          <Tag color="blue" style={{ cursor: 'pointer' }}>{r.name}</Tag>
        </a>
      ),
    },
    { title: '文章数', dataIndex: 'totalCount', width: 120 },
    {
      title: '操作',
      valueType: 'option',
      width: 100,
      render: (_, r) => (
        <Space.Compact>
          <Tooltip title="前台查看">
            <Button
              size="small"
              icon={<ExportOutlined />}
              onClick={() => window.open(`/tag/${encodeURIComponent(r.name)}`, '_blank')}
            />
          </Tooltip>
          {Number(r.totalCount) > 0 ? (
            <Tooltip title="该标签关联文章，无法删除">
              <Button
                size="small"
                danger
                icon={<DeleteOutlined />}
                onClick={() => message.warning(`该标签关联 ${r.totalCount} 篇文章，无法删除；请先在文章中移除该标签。`)}
              />
            </Tooltip>
          ) : (
            <Popconfirm title={`确认删除标签「${r.name}」？`} onConfirm={() => handleDelete(r.name)}>
              <Tooltip title="删除">
                <Button size="small" danger icon={<DeleteOutlined />} />
              </Tooltip>
            </Popconfirm>
          )}
        </Space.Compact>
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
