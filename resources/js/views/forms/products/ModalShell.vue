<template>
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-card">
        <button class="close" @click="$emit('close')">✖</button>
        <slot/>
      </div>
    </div>
  </template>

  <script>export default { name:'ModalShell' };</script>
  <style scoped>
  .modal-overlay{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; justify-content:center; align-items:center; z-index:2000; }
  .modal-card{ background:#fff; padding:28px; border-radius:18px; max-width:90vw; max-height:90vh; overflow:auto; position:relative; }
  .close{ position:absolute; top:12px; right:12px; border:none; background:none; font-size:22px; cursor:pointer; }
  </style>
