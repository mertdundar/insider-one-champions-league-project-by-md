<template>
    <section class="panel">
        <header class="panel-header">{{ title }}</header>
        <div class="panel-body"><slot /></div>
    </section>
</template>

<script setup>
defineProps({ title: { type: String, required: true } });
</script>

<style scoped>
.panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.panel-header {
    background: var(--table-header-bg);
    color: var(--table-header-ink);
    padding: 12px 16px;
    font-weight: 600;
}
.panel-body { padding: 0; }
</style>
