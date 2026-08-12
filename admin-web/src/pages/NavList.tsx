import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, Tag, Space, Tooltip, message } from 'antd'
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import type { Nav } from '../types/api'

export default function NavList() {
  const navigate = useNavigate()
  const actionRef = useRef<ActionType>(null)

  const handleDelete = async (id: number) => {
    try {
      await api.navDelete(id)
      message.success('导航已删除。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<Nav>[] = [
    { title: 'ID', dataIndex: 'id', width: 55 },
    {
      title: '名称',
      dataIndex: 'name',
      render: (_, r) => <a onClick={() => navigate(`/navs/${r.id}/edit`)}>{r.name}</a>,
    },
    { title: '父级', dataIndex: 'pid', width: 90, render: (_, r) => (r.pid ? `#${r.pid}` : '顶级') },
    {
      title: 'URL / 路由',
      dataIndex: 'url',
      ellipsis: true,
      render: (_, r) => (r.route === 1 ? <Tag color="blue">{r.url}</Tag> : r.url),
    },
    { title: '排序', dataIndex: 'sort_order', width: 80 },
    {
      title: '操作',
      valueType: 'option',
      width: 72,
      render: (_, r) => (
        <Space.Compact>
          <Tooltip title="编辑">
            <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/navs/${r.id}/edit`)} />
          </Tooltip>
          <Popconfirm title="确认删除该导航？子导航将一并删除。" onConfirm={() => handleDelete(r.id)}>
            <Tooltip title="删除">
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space.Compact>
      ),
    },
  ]

  const request = async () => {
    const res = await api.navs()
    return { data: res.items, total: res.items.length, success: true }
  }

  return (
    <ProTable
      headerTitle="导航列表"
      actionRef={actionRef}
      rowKey="id"
      columns={columns}
      request={request}
      search={false}
      pagination={false}
      toolBarRender={() => [
        <Button key="create" type="primary" icon={<PlusOutlined />} onClick={() => navigate('/navs/create')}>
          新建导航
        </Button>,
      ]}
    />
  )
}
