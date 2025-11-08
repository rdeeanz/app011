import { createApp, h, DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import '../css/app.css'

const appName = import.meta.env.VITE_APP_NAME || 'Detik Clone'

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => {
    try {
      return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue'))
    } catch (error) {
      console.error('Failed to resolve page component:', name, error)
      return resolvePageComponent('./Pages/Error.vue', import.meta.glob<DefineComponent>('./Pages/**/*.vue'))
    }
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      
    // Global error handler
    app.config.errorHandler = (err, _instance, info) => {
      console.error('Vue error:', err, info)
    }
    
    return app.mount(el)
  },
  progress: {
    color: '#C4171D',
    showSpinner: true,
  },
})