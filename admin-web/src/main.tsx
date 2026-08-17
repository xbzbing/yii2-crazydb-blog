import React from 'react'
import { createRoot } from 'react-dom/client'
import { HashRouter } from 'react-router-dom'
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
          {/* Hash 路由：后台内部跳转走 #/xxx，不依赖服务器 history 回退，
              与前台（BrowserRouter）彻底解耦；/admin 前缀仅由 nginx 静态托管提供。 */}
          <HashRouter>
            <App />
          </HashRouter>
        </AntApp>
      </ConfigProvider>
    </React.StrictMode>,
  )
}
