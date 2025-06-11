{{-- Audio Initialization Banner --}}
<div id="audio-init-banner" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🔊</span>
            <div>
                <h3 class="font-semibold text-yellow-800">Aktifkan Audio Antrian</h3>
                <p class="text-sm text-yellow-700">Klik tombol untuk mengaktifkan suara panggilan antrian</p>
            </div>
        </div>
        <button 
            id="activate-audio-btn"
            onclick="initializeAudio()"
            class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-medium transition-colors"
        >
            Aktifkan Audio
        </button>
    </div>
</div>

{{-- Debug Panel --}}
<div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
    <h3 class="font-bold text-blue-800 mb-3">🔧 Audio Debug Panel</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
        <button 
            onclick="window.testQueueCall('Test nomor antrian A001 silakan menuju ruang periksa')"
            class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors"
        >
            🧪 Test Audio
        </button>
        <button 
            onclick="window.stopQueueAudio()"
            class="px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors"
        >
            🛑 Stop Audio
        </button>
        <button 
            onclick="window.getAudioStatus()"
            class="px-3 py-2 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors"
        >
            📊 Status
        </button>
        <button 
            onclick="initializeAudio()"
            class="px-3 py-2 bg-purple-500 text-white rounded text-sm hover:bg-purple-600 transition-colors"
        >
            🔄 Re-init
        </button>
    </div>
    
    <div class="text-xs text-gray-600 bg-white p-2 rounded border">
        <div id="audio-status">Status: Checking...</div>
    </div>
</div>

{{-- Session Debug Info --}}
@if(session('queue_called'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 rounded-lg">
        <strong>🔊 Session Queue Called:</strong> {{ session('queue_called.number') ?? 'N/A' }}
        <br>
        <small>{{ session('queue_called.message') ?? session('queue_called') }}</small>
    </div>
@endif

<script>
let audioInitialized = false;
let statusInterval;

async function initializeAudio() {
    console.log('🎵 Manual audio initialization...');
    
    const button = document.getElementById('activate-audio-btn');
    if (button) {
        button.disabled = true;
        button.textContent = 'Mengaktifkan...';
    }
    
    try {
        const success = await window.QueueAudio.initializeAudio();
        
        if (success) {
            audioInitialized = true;
            
            // Hide banner
            const banner = document.getElementById('audio-init-banner');
            if (banner) {
                banner.style.transition = 'opacity 0.5s, height 0.5s';
                banner.style.opacity = '0';
                banner.style.height = '0';
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 500);
            }
            
            console.log('✅ Audio initialization successful');
        } else {
            console.error('❌ Audio initialization failed');
            alert('Gagal mengaktifkan audio. Pastikan browser mendukung Text-to-Speech.');
            
            if (button) {
                button.disabled = false;
                button.textContent = 'Coba Lagi';
            }
        }
    } catch (error) {
        console.error('❌ Audio initialization error:', error);
        if (button) {
            button.disabled = false;
            button.textContent = 'Coba Lagi';
        }
    }
    
    updateAudioStatus();
}

function updateAudioStatus() {
    const statusDiv = document.getElementById('audio-status');
    if (statusDiv && window.QueueAudio) {
        const status = window.QueueAudio.getStatus();
        const speechStatus = status.speechSynthesisStatus;
        
        statusDiv.innerHTML = `
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <strong>Audio Status:</strong><br>
                    Initialized: ${audioInitialized ? '✅ Yes' : '❌ No'}<br>
                    Playing: ${status.isPlaying ? '🔊 Yes' : '🔇 No'}<br>
                    Speech API: ${status.speechSynthesisSupported ? '✅ Available' : '❌ Not Available'}
                </div>
                <div>
                    <strong>Speech Synthesis:</strong><br>
                    Speaking: ${speechStatus?.speaking ? '✅ Yes' : '❌ No'}<br>
                    Pending: ${speechStatus?.pending ? '⏳ Yes' : '✅ No'}<br>
                    Paused: ${speechStatus?.paused ? '⏸️ Yes' : '▶️ No'}
                </div>
            </div>
            <div class="mt-2 pt-2 border-t">
                <strong>Last Message:</strong> ${status.lastMessage || 'None'}<br>
                <strong>Last Play:</strong> ${status.lastPlayTime ? new Date(status.lastPlayTime).toLocaleTimeString() : 'Never'}
            </div>
        `;
    }
}

// Auto-check status every 3 seconds
function startStatusMonitoring() {
    statusInterval = setInterval(updateAudioStatus, 3000);
}

function stopStatusMonitoring() {
    if (statusInterval) {
        clearInterval(statusInterval);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Dokter header DOM loaded');
    
    // Start status monitoring
    setTimeout(() => {
        updateAudioStatus();
        startStatusMonitoring();
    }, 1000);
    
    // Auto-hide banner if voices are already available
    setTimeout(async () => {
        if (window.speechSynthesis && window.QueueAudio) {
            const voices = await window.QueueAudio.getVoicesWithTimeout();
            if (voices.length > 0) {
                console.log('🎤 Voices already available, auto-initializing...');
                await initializeAudio();
            }
        }
    }, 2000);
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopStatusMonitoring();
    if (window.QueueAudio) {
        window.QueueAudio.stop();
    }
});

// Debug Livewire events in dokter panel
document.addEventListener('livewire:initialized', () => {
    console.log('🔧 Dokter panel - Livewire initialized');
    
    Livewire.on('queue-called', (message) => {
        console.log('🔧 Dokter panel - queue-called event received:', message);
        updateAudioStatus();
    });
});

// Session-based audio fallback
@if(session('queue_called'))
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const queueData = @json(session('queue_called'));
            console.log('🎯 Processing session queue data:', queueData);
            
            if (queueData && window.QueueAudio) {
                let message;
                
                // Handle different session data formats
                if (typeof queueData === 'string') {
                    message = queueData;
                } else if (queueData.message) {
                    message = queueData.message;
                } else if (queueData.number) {
                    message = `Nomor antrian ${queueData.number} silakan menuju ruang periksa`;
                } else {
                    message = 'Silakan menuju ruang periksa';
                }
                
                console.log('🎯 Triggering session audio:', message);
                window.QueueAudio.playQueueAudio(message);
            }
        }, 1500); // Delay to ensure audio is ready
    });
    
    @php(session()->forget('queue_called'))
@endif
</script>