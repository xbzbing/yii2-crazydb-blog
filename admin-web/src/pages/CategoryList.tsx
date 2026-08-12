import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, Space, Tooltip, message } from 'antd'
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import type { Category } from '../types/api'

interface CategoryRow extends Category {
  parentName?: string
  depth: number
}

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

  const columns: ProColumns<CategoryRow>[] = [
    {
      title: '名称',
      dataIndex: 'name',
      render: (_, r) => (
        <a onClick={() => navigate(`/categories/${r.id}/edit`)}>
          {r.depth > 0 ? '└ ' : ''}
          {r.name}
        </a>
      ),
    },
    { title: '别名', dataIndex: 'alias', width: 200, ellipsis: true },
    { title: '父分类', dataIndex: 'parentName', width: 120, render: (_, r) => r.parentName || <span style={{ color: '#bbb' }}>顶级</span> },
    { title: '排序', dataIndex: 'sort_order', width: 80 },
    {
      title: '操作',
      valueType: 'option',
      width: 72,
      render: (_, r) => (
        <Space.Compact>
          <Tooltip title="编辑">
            <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/categories/${r.id}/edit`)} />
          </Tooltip>
          <Popconfirm title="确认删除该分类？" onConfirm={() => handleDelete(r.id)}>
            <Tooltip title="删除">
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space.Compact>
      ),
    },
  ]

  const request = async () => {
    const res = await api.categories()
    const list = res.items || []
    const nameById: Record<number, string> = {}
    list.forEach((c) => (nameById[c.id] = c.name))
    const rows: CategoryRow[] = []
    // 每个顶级分类后紧跟其子分类（按 sort_order 排序）
    list
      .filter((c) => c.pid === 0)
      .sort((a, b) => b.sort_order - a.sort_order)
      .forEach((top) => {
        rows.push({ ...top, parentName: undefined, depth: 0 })
        list
          .filter((c) => c.pid === top.id)
          .sort((a, b) => b.sort_order - a.sort_order)
          .forEach((child) => rows.push({ ...child, parentName: nameById[child.pid] || '', depth: 1 }))
      })
    return { data: rows, total: rows.length, success: true }
  }

  return (
    <ProTable<CategoryRow>
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
