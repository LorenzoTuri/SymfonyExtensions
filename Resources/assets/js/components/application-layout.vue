<template>
    <div @click="onClick">
        Test
    </div>
</template>
<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';

@Component({
})
class ApplicationLayout extends Vue {
    message: string = 'Hello!'
    onClick (): void {
        window.alert(this.message)
    }
}
export default ApplicationLayout
</script>