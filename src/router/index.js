import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/about',
    name: 'about',
    component: () => import(/* webpackChunkName: "about" */ '../views/AboutView.vue')
  },
  {
    path: '/customers',
    name: 'customers',
    component: () => import(/* webpackChunkName: "about" */ '../views/Customers.vue')
  },
  {
    path: '/employee',
    name: 'employee',
    component: () => import(/* webpackChunkName: "about" */ '../views/employee.vue')
  },
  {
    path: '/contacts',
    name: 'contacts',
    component: () => import(/* webpackChunkName: "about" */ '../views/contacts.vue')
  },
  {
    path: '/add',
    name: 'AddCustomers',
    component: () => import(/* webpackChunkName: "about" */ '../views/AddCustomers.vue')
  },
  {
    path: '/AE',
    name: 'AddEmployee',
    component: () => import(/* webpackChunkName: "about" */ '../views/AddEmployee.vue')
  },
  {
    path: '/AC',
    name: 'AddContacts',
    component: () => import(/* webpackChunkName: "about" */ '../views/Addcontacts.vue')
  },
  {
    path: '/ACC',
    name: 'Customer_crud',
    component: () => import(/* webpackChunkName: "about" */ '../views/Customer_crud.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/EC',
    name: 'Employees_crud',
    component: () => import(/* webpackChunkName: "about" */ '../views/Employees_crud.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pro',
    name: 'api_Product',
    component: () => import(/* webpackChunkName: "about" */ '../views/product_edit.vue')
  },
   {
    path: '/AS',
    name: 'api_asset',
    component: () => import(/* webpackChunkName: "about" */ '../views/asset_edit.vue'),
     meta: { requiresAuth: true }
  },
  {
   path: '/login',
    name: 'login',
    
    component: () => import(/* webpackChunkName: "about" */ '../views/login.vue')
  },
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})
router.beforeEach((to, from, next) => {

  // ตรวจสอบสถานะ Login จาก localStorage
  const isLoggedIn = localStorage.getItem('adminLogin') === 'true'

  // ถ้าหน้านั้นต้อง Login แต่ยังไม่ได้ Login
  if (to.meta.requiresAuth && !isLoggedIn) {
    next('/login')
  }

  // ถ้า Login แล้ว ไม่ต้องกลับไปหน้า Login
  else if (to.path === '/login' && isLoggedIn) {
    next('/')
  }

  // กรณีอื่น ๆ อนุญาตให้เข้าหน้าได้
  else {
    next()
  }
})
export default router
