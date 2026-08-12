import React from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { App as AntApp, ConfigProvider } from 'antd'
import zhCN from 'antd/locale/zh_CN'
import dayjs from 'dayjs'
import 'dayjs/locale/zh-cn'
import App from './App'
import './index.css'

dayjs.locale('zh-cn')

const rootEl = document.getElementById('root')
if (rootEl) {
  createRoot(rootEl).render(
    <React.StrictMode>
      <ConfigProvider locale={zhCN}>
        <AntApp>
          <BrowserRouter basename="/admin">
            <App />
          </BrowserRouter>
        </AntApp>
      </ConfigProvider>
    </React.StrictMode>,
  )
}
