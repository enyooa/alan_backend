// resources/js/plugins/axios.js
import axios from 'axios';

/*  ─── 1.  Больше НЕ прописываем baseURL  ─── */
// axios.defaults.baseURL = '/api'   ←  УДАЛЕНО

/*  ─── 2.  Токен сразу после F5 ─── */
const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common.Authorization = `Bearer ${token}`;
}

/*  ─── 3.  Interceptor: подставляем свежий токен перед каждым запросом ─── */
axios.interceptors.request.use(cfg => {
  const t = localStorage.getItem('token');
  if (t) cfg.headers.Authorization = `Bearer ${t}`;
  return cfg;
});

/*  ─── 4.  Interceptor: если 401 → выбрасываем на /login ─── */
axios.interceptors.response.use(
  res => res,
  err => {
    if (err.response && err.response.status === 401) {
      localStorage.clear();
      delete axios.defaults.headers.common.Authorization;
      window.location = '/login';
    }
    return Promise.reject(err);
  }
);

export default axios;
