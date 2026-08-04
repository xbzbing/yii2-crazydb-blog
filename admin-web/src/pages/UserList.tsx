import { useRef, useState } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Tag, Space, Tooltip, message, Modal, Descriptions } from 'antd'
import { StopOutlined, CheckCircleOutlined } from '@ant-design/icons'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { User } from '../types/api'

const ROLE_MAP = {
  1: { text: '会员', color: 'default' },
  8: { text: '编辑', color: 'blue' },
  16: { text: '管理员', color: 'red' },
}

const STATUS_MAP = {
  1: { text: '正常', color: 'green' },
  2: { text: '未激活', color: 'orange' },
  4: { text: '已禁用', color: 'red' },
  8: { text: '已删除', color: 'default' },
}

export default function UserList() {
  const actionRef = useRef<ActionType>(null)
  const [banTarget, setBanTarget] = useState<User | null>(null)
  const [confirming, setConfirming] = useState(false)

  const handleToggle = async (action: string, id: number) => {
    setConfirming(true)
    try {
      const data = await api.userAction(action, id)
      message.success(data?.message || (action === 'ban' ? '用户已禁用。' : '用户已启用。'))
      setBanTarget(null)
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setConfirming(false)
    }
  }

  const columns: ProColumns<User>[] = [
    { title: 'ID', dataIndex: 'id', width: 60, search: false },
    { title: '用户名', dataIndex: 'username', width: 130 },
    { title: '昵称', dataIndex: 'nickname', width: 130 },
    { title: '邮箱', dataIndex: 'email', ellipsis: true },
    {
      title: '角色',
      dataIndex: 'role',
      width: 90,
      search: false,
      render: (_, r) => {
        const m = ROLE_MAP[r.role as keyof typeof ROLE_MAP] || ROLE_MAP[1]
        return <Tag color={m.color}>{m.text}</Tag>
      },
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 90,
      search: false,
      render: (_, r) => {
        const m = STATUS_MAP[r.status as keyof typeof STATUS_MAP] || STATUS_MAP[1]
        return <Tag color={m.color}>{m.text}</Tag>
      },
    },
    {
      title: '注册时间',
      dataIndex: 'register_time',
      width: 110,
      search: false,
      render: (_, r) => (r.register_time ? dayjs.unix(r.register_time).format('YYYY-MM-DD') : '-'),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 72,
      search: false,
      render: (_, r) => (
        <Space.Compact>
          {r.status !== 4 ? (
            <Tooltip title="禁用">
              <Button size="small" danger icon={<StopOutlined />} onClick={() => setBanTarget(r)} />
            </Tooltip>
          ) : (
            <Tooltip title="启用">
              <Button size="small" icon={<CheckCircleOutlined />} onClick={() => handleToggle('unban', r.id)} />
            </Tooltip>
          )}
        </Space.Compact>
      ),
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    // ProTable 搜索表单以列 dataIndex 为参数名（username/nickname/email），
    // 后端统一按 keyword 模糊匹配三者，取任一非空值
    const keyword = (params.keyword as string) || (params.username as string) || (params.nickname as string) || (params.email as string) || ''
    const res = await api.users({ page: params.current || 1, keyword })
    return { data: res.items, total: res.total, success: true }
  }

  return (
    <>
      <ProTable
        headerTitle="用户列表"
        actionRef={actionRef}
        rowKey="id"
        columns={columns}
        request={request}
        pagination={{ defaultPageSize: 20, showSizeChanger: false }}
        scroll={{ x: 'max-content' }}
      />

      {/* 禁用确认弹窗 */}
      <Modal
        title="确认禁用用户"
        open={banTarget !== null}
        onOk={() => banTarget && handleToggle('ban', banTarget.id)}
        onCancel={() => setBanTarget(null)}
        confirmLoading={confirming}
        okText="确认禁用"
        okButtonProps={{ danger: true }}
        cancelText="取消"
      >
        {banTarget && (
          <Descriptions column={1} size="small">
            <Descriptions.Item label="用户名">{banTarget.username}</Descriptions.Item>
            <Descriptions.Item label="昵称">{banTarget.nickname}</Descriptions.Item>
            <Descriptions.Item label="邮箱">{banTarget.email || '-'}</Descriptions.Item>
            <Descriptions.Item label="注册时间">
              {banTarget.register_time ? dayjs.unix(banTarget.register_time).format('YYYY-MM-DD HH:mm') : '-'}
            </Descriptions.Item>
            <Descriptions.Item label="说明">禁用后该用户将无法登录与发表评论，确认继续？</Descriptions.Item>
          </Descriptions>
        )}
      </Modal>
    </>
  )
}
