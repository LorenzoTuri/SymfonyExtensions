import Vue from 'vue'
import { BootstrapVue/*, IconsPlugin */} from 'bootstrap-vue'

// Make BootstrapVue available throughout your project
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
//Vue.use(IconsPlugin)

import applicationLayout from './components/application-layout'

window.application = new Vue({
    el: '#application',
    components: {
        applicationLayout
    },
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    render: h => h(applicationLayout)
})