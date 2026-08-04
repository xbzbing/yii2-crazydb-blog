import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, Tag, message } from 'antd'
import { PlusOutlined } from '@ant-design/icons'
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
    { title: 'ID', dataIndex: 'id', width: 70 },
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
      width: 150,
      render: (_, r) => [
        <a key="edit" onClick={() => navigate(`/navs/${r.id}/edit`)}>
          编辑
        </a>,
        <Popconfirm key="del" title="确认删除该导航？子导航将一并删除。" onConfirm={() => handleDelete(r.id)}>
          <a style={{ color: '#ff4d4f' }}>删除</a>
        </Popconfirm>,
      ],
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
