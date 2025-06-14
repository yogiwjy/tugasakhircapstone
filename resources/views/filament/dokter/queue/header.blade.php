{{-- File: resources/views/filament/dokter/queue/header.blade.php --}}
{{-- Minimal header - Audio always ready, no UI needed --}}

<script>
// ==================== MINIMAL SETUP - ALWAYS READY AUDIO ====================

// Simple event registration for panel dokter
function setupMinimalAudioEvents() {
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', function(message) {
            // Direct audio call - no UI feedback needed
            window.handleQueueCall(message);
        });
    } else {
        setTimeout(setupMinimalAudioEvents, 500);
    }
}

// Initialize immediately
setupMinimalAudioEvents();

// Backup initialization
document.addEventListener('DOMContentLoaded', setupMinimalAudioEvents);
document.addEventListener('livewire:initialized', setupMinimalAudioEvents);

// Emergency fallback - ensure audio is always attempted
setInterval(() => {
    if (window.QueueAudio && !window.QueueAudioState?.isInitialized) {
        window.QueueAudio.initializeAudio();
    }
}, 10000); // Check every 10 seconds

console.log('✅ Minimal Audio Header - Always Ready Mode');
</script>

{{-- 
Optional: Completely empty header alternative
Just remove everything above and use this instead:

<script>
console.log('🔊 Audio ready - no header needed');
</script>
--}}