<script setup>
import { motion } from 'motion-v';

defineProps({
    delay: { type: Number, default: 0 },
    y: { type: Number, default: 28 },
    duration: { type: Number, default: 0.65 },
    once: { type: Boolean, default: true },
    amount: { type: [Number, String], default: 0.2 },
    as: { type: String, default: 'div' },
    className: { type: String, default: '' },
});
</script>

<template>
    <motion.div
        :initial="{ opacity: 0, y }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once, amount }"
        :transition="{
            duration,
            delay,
            ease: [0.22, 1, 0.36, 1],
        }"
        :class="className"
    >
        <slot />
    </motion.div>
</template>
