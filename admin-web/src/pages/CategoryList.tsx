import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, message } from 'antd'
import { PlusOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import type { Category } from '../types/api'

export default function CategoryList() {
  const navigate = useNavigate()
  const actionRef = useRef<ActionType>(null)

  const handleDelete = async (id: number) => {
    try {
      await api.categoryDelete(id)
      message.success('分类已删除。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<Category>[] = [
    { title: 'ID', dataIndex: 'id', width: 70 },
    { title: '名称', dataIndex: 'name', render: (_, r) => <a onClick={() => navigate(`/categories/${r.id}/edit`)}>{r.name}</a> },
    { title: '别名', dataIndex: 'alias', width: 200 },
    { title: '父分类', dataIndex: 'pid', width: 100, render: (_, r) => (r.pid ? `#${r.pid}` : '顶级') },
    { title: '排序', dataIndex: 'sort_order', width: 80 },
    {
      title: '操作',
      valueType: 'option',
      width: 150,
      render: (_, r) => [
        <a key="edit" onClick={() => navigate(`/categories/${r.id}/edit`)}>
          编辑
        </a>,
        <Popconfirm key="del" title="确认删除该分类？" onConfirm={() => handleDelete(r.id)}>
          <a style={{ color: '#ff4d4f' }}>删除</a>
        </Popconfirm>,
      ],
    },
  ]

  const request = async () => {
    const res = await api.categories()
    return { data: res.items, total: res.items.length, success: true }
  }

  return (
    <ProTable
      headerTitle="分类列表"
      actionRef={actionRef}
      rowKey="id"
      columns={columns}
      request={request}
      search={false}
      pagination={false}
      toolBarRender={() => [
        <Button key="create" type="primary" icon={<PlusOutlined />} onClick={() => navigate('/categories/create')}>
          新建分类
        </Button>,
      ]}
    />
  )
}
