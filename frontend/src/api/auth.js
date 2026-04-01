import client from './client';

export const postRegisterRequest = (email) =>
  client.post('/auth/register/request', { email }).then((r) => r.data);

export const getRegisterVerify = (token) =>
  client.get('/auth/register/verify', { params: { token } }).then((r) => r.data);

export const postRegisterComplete = (token, name, password) =>
  client.post('/auth/register/complete', { token, name, password }).then((r) => r.data);

export const postLogin = (email, password) =>
  client.post('/auth/login', { email, password }).then((r) => r.data);

export const getMe = () =>
  client.get('/auth/me').then((r) => r.data);

export const postRefresh = () =>
  client.post('/auth/refresh').then((r) => r.data);

export const postLogout = () =>
  client.post('/auth/logout').then((r) => r.data);

export const postForgotPassword = (email) =>
    client.post('/auth/password/forgot', { email }).then((r) => r.data);

export const getPasswordVerify = (token) =>
    client.get('/auth/password/verify', { params: { token } }).then((r) => r.data);

export const postResetPassword = (token, password) =>
    client.post('/auth/password/reset', { token, password }).then((r) => r.data);

export const postChangePassword = (currentPassword, newPassword) =>
    client.post('/auth/password/change', { current_password: currentPassword, new_password: newPassword }).then((r) => r.data);