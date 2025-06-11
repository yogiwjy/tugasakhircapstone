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
    
    <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
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
        <button 
            onclick="testMultipleAudio()"
            class="px-3 py-2 bg-orange-500 text-white rounded text-sm hover:bg-orange-600 transition-colors"
        >
            🔥 Multi Test
        </button>
    </div>
    
    <div class="text-xs text-gray-600 bg-white p-3 rounded border">
        <div id="audio-status">Status: Checking...</div>
    </div>
    
    <div class="mt-3 text-xs text-gray-500 bg-gray-50 p-2 rounded">
        <strong>💡 Tips:</strong> Jika audio error, coba refresh halaman dan aktifkan ulang
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
        // Ensure QueueAudio is available
        if (!window.QueueAudio) {
            throw new Error('QueueAudio not loaded');
        }
        
        const success = await window.QueueAudio.initializeAudio();
        
        if (success) {
            audioInitialized = true;
            
            // Hide banner with smooth animation
            const banner = document.getElementById('audio-init-banner');
            if (banner) {
                banner.style.transition = 'opacity 0.5s, transform 0.5s';
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 500);
            }
            
            console.log('✅ Audio initialization successful');
            
            // Test audio after successful init
            setTimeout(() => {
                window.QueueAudio.playQueueAudio('Audio berhasil diaktifkan, sistem siap digunakan');
            }, 1000);
            
        } else {
            throw new Error('Audio initialization failed');
        }
    } catch (error) {
        console.error('❌ Audio initialization error:', error);
        
        // Show user-friendly error message
        alert('Gagal mengaktifkan audio. Pastikan:\n' +
              '1. Browser mendukung Text-to-Speech\n' +
              '2. Tidak ada aplikasi lain yang menggunakan audio\n' +
              '3. Coba refresh halaman dan aktifkan ulang');
        
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
        try {
            const status = window.QueueAudio.getStatus();
            const speechStatus = status.speechSynthesisStatus;
            
            statusDiv.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                    <div>
                        <strong class="text-blue-600">Audio Status:</strong><br>
                        Initialized: ${audioInitialized ? '✅ Yes' : '❌ No'}<br>
                        Global Init: ${status.audioInitialized ? '✅ Yes' : '❌ No'}<br>
                        Currently Playing: ${status.isPlaying ? '🔊 Yes' : '🔇 No'}
                    </div>
                    <div>
                        <strong class="text-green-600">Speech API:</strong><br>
                        Available: ${status.speechSynthesisSupported ? '✅ Yes' : '❌ No'}<br>
                        Speaking: ${speechStatus?.speaking ? '✅ Yes' : '❌ No'}<br>
                        Pending: ${speechStatus?.pending ? '⏳ Yes' : '✅ No'}
                    </div>
                    <div>
                        <strong class="text-purple-600">Last Activity:</strong><br>
                        Voices: ${status.voicesCount || 0}<br>
                        Voice: ${status.preferredVoice || 'None'}<br>
                        Last Play: ${status.lastPlayTime ? new Date(status.lastPlayTime).toLocaleTimeString() : 'Never'}
                    </div>
                </div>
            `;
        } catch (error) {
            statusDiv.innerHTML = `<span class="text-red-600">❌ Error getting status: ${error.message}</span>`;
        }
    } else {
        if (statusDiv) {
            statusDiv.innerHTML = '<span class="text-red-600">❌ QueueAudio not available</span>';
        }
    }
}

function testMultipleAudio() {
    console.log('🔥 Testing multiple audio calls...');
    
    const messages = [
        'Nomor antrian A001 silakan ke loket 1',
        'Nomor antrian B002 silakan ke loket 2', 
        'Nomor antrian C003 silakan ke loket 3'
    ];
    
    messages.forEach((message, index) => {
        setTimeout(() => {
            console.log(`🔊 Playing message ${index + 1}:`, message);
            if (window.QueueAudio) {
                window.QueueAudio.playQueueAudio(message);
            }
        }, index * 4000); // 4 second delay between each
    });
}

// Auto-check status every 5 seconds
function startStatusMonitoring() {
    statusInterval = setInterval(updateAudioStatus, 5000);
}

function stopStatusMonitoring() {
    if (statusInterval) {
        clearInterval(statusInterval);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Audio controls DOM loaded');
    
    // Start status monitoring
    setTimeout(() => {
        updateAudioStatus();
        startStatusMonitoring();
    }, 1000);
    
    // Check if voices are already available
    setTimeout(async () => {
        if (window.speechSynthesis && window.QueueAudio) {
            try {
                const voices = speechSynthesis.getVoices();
                if (voices.length > 0) {
                    console.log('🎤 Voices already available, ready for manual init');
                    // Don't auto-init, let user click button
                }
            } catch (error) {
                console.log('⚠️ Voice check failed:', error);
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

// Enhanced Livewire event handling
document.addEventListener('livewire:initialized', () => {
    console.log('🔧 Enhanced Livewire initialized for audio');
    
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', (message) => {
            console.log('🔧 Queue-called event received:', message);
            updateAudioStatus();
            
            // Visual feedback
            const statusDiv = document.getElementById('audio-status');
            if (statusDiv) {
                statusDiv.style.border = '2px solid #10b981';
                setTimeout(() => {
                    statusDiv.style.border = '1px solid #d1d5db';
                }, 2000);
            }
        });
    }
});

// Session-based audio fallback with error handling
@if(session('queue_called'))
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            try {
                const queueData = @json(session('queue_called'));
                console.log('🎯 Processing session queue data:', queueData);
                
                if (queueData && window.QueueAudio && audioInitialized) {
                    let message;
                    
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
            } catch (error) {
                console.error('❌ Session audio error:', error);
            }
        }, 2000); // Wait for audio to be ready
    });
    
    @php(session()->forget('queue_called'))
@endif

// Global error handler for unhandled audio errors
window.addEventListener('error', function(e) {
    if (e.message && e.message.includes('speech')) {
        console.warn('🔊 Global speech error caught:', e.message);
    }
});
</script>