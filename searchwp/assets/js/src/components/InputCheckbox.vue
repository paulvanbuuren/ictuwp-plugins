<template>
    <div class="searchwp-input-checkbox">
        <input
            type="checkbox"
            :disabled="disabled"
            :id="'searchwp_input_checkbox_' + _uid"
            v-model="val"/>
        <label v-if="tooltip.length" :for="'searchwp_input_checkbox_' + _uid">
            <searchwp-tooltip :content="tooltip">{{ label }}</searchwp-tooltip>
        </label>
        <label v-else :for="'searchwp_input_checkbox_' + _uid">{{ label }}</label>
    </div>
</template>

<script>
import Tooltip from './Tooltip.vue';

export default {
    name: 'SearchwpInputCheckbox',
    components: {
        'searchwp-tooltip': Tooltip
    },
    data() {
        return {
            val: this['checked'],
        };
    },
    model: {
        prop: 'checked',
        event: 'change'
    },
    props: {
        checked: {
            type: Boolean
        },
        label: {
            type: String,
            default: ''
        },
        tooltip: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    watch: {
        val(value) {
            this.$emit('change', value);
        }
    }
}
</script>

<style lang="scss">
    .searchwp-input-checkbox {
        display: flex;
        align-items: center;
        line-height: 1.4;
        padding-top: 0.5em;

        input {
            display: inline-block;
            margin-top: 1px;
            margin-right: 0.6em;
        }
    }
</style>
