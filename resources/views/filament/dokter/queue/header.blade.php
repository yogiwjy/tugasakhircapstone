{{-- 
    File: resources/views/filament/dokter/queue/header.blade.php
    REPLACE OR CREATE THIS FILE WITH THIS CONTENT
--}}

{{-- Audio Initialization Banner --}}
<div id="audio-init-banner" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg shadow-sm">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🔊</span>
            <div>
                <h3 class="font-semibold text-yellow-800">Aktifkan Audio Antrian</h3>
                <p class="text-sm text-yellow-700">Klik tombol untuk mengaktifkan suara panggilan antrian (wajib untuk browser)</p>
            </div>
        </div>
        <button 
            id="activate-audio-btn"
            onclick="initializeAudio()"
            class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
        >
            Aktifkan Audio
        </button>
    </div>
</div>

{{-- Audio Controls Panel --}}
<div id="audio-controls-panel" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg shadow-sm">
    <h3 class="font-bold text-blue-800 mb-3">🎵 Audio Controls - Panel Dokter</h3>
    
    {{-- Control Buttons --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-4">
        <button 
            onclick="window.testQueueCall('Test nomor antrian A001 silakan menuju ruang periksa')"
            class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
            title="Test audio dengan pesan contoh"
        >
            🧪 Test Audio
        </button>
        
        <button 
            onclick="window.stopQueueAudio()"
            class="px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
            title="Stop audio yang sedang berjalan"
        >
            🛑 Stop Audio
        </button>
        
        <button 
            onclick="window.getAudioStatus()"
            class="px-3 py-2 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
            title="Cek status audio di console"
        >
            📊 Status
        </button>
        
        <button 
            onclick="window.reinitializeAudio()"
            class="px-3 py-2 bg-purple-500 text-white rounded text-sm hover:bg-purple-600 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1"
            title="Inisialisasi ulang sistem audio"
        >
            🔄 Re-init
        </button>
        
        <button 
            onclick="testMultipleAudio()"
            class="px-3 py-2 bg-orange-500 text-white rounded text-sm hover:bg-orange-600 transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-1"
            title="Test audio berurutan"
        >
            🔥 Multi Test
        </button>
        
        <button 
            onclick="window.checkAudioSupport()"
            class="px-3 py-2 bg-gray-500 text-white rounded text-sm hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-1"
            title="Cek dukungan browser"
        >
            🔍 Check Support
        </button>
    </div>
    
    {{-- Status Display --}}
    <div class="text-xs text-gray-600 bg-white p-3 rounded border shadow-sm">
        <div id="audio-status" class="font-mono">Status: Checking...</div>
    </div>
    
    {{-- Tips Section --}}
    <div class="mt-3 text-xs text-gray-500 bg-gray-50 p-2 rounded">
        <strong>💡 Tips:</strong> 
        <span id="audio-tips">Klik "Aktifkan Audio" terlebih dahulu, kemudian test dengan tombol di atas</span>
    </div>

    {{-- Console Instructions --}}
    <div class="mt-2 text-xs text-gray-400 bg-gray-100 p-2 rounded">
        <strong>🔧 Debug:</strong> Buka console browser (F12) untuk melihat log detail
    </div>
</div>

{{-- Session Debug Info (only if exists) --}}
@if(session('queue_called'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 rounded-lg">
        <strong>🔊 Session Queue Called:</strong> {{ session('queue_called.number') ?? 'N/A' }}
        <br>
        <small class="text-green-700">{{ session('queue_called.message') ?? session('queue_called') }}</small>
    </div>
@endif

{{-- JavaScript untuk Audio Controls --}}
<script>
// ==================== GLOBAL VARIABLES ====================
let audioInitialized = false;
let statusInterval = null;
let initializationInProgress = false;

// ==================== MAIN FUNCTIONS ====================

/**
 * Initialize audio system dengan error handling lengkap
 */
async function initializeAudio() {
    // Prevent multiple simultaneous initializations
    if (initializationInProgress) {
        console.log('⏳ Initialization already in progress...');
        return;
    }
    
    initializationInProgress = true;
    console.log('🎵 Manual audio initialization started...');
    
    const button = document.getElementById('activate-audio-btn');
    const banner = document.getElementById('audio-init-banner');
    const tips = document.getElementById('audio-tips');
    
    // Update UI
    if (button) {
        button.disabled = true;
        button.textContent = 'Mengaktifkan...';
        button.className = button.className.replace('bg-yellow-500', 'bg-gray-400');
    }
    
    try {
        // Pre-checks
        if (!window.QueueAudio) {
            throw new Error('QueueAudio script tidak dimuat. Silakan refresh halaman.');
        }
        
        if (!('speechSynthesis' in window)) {
            throw new Error('Browser tidak mendukung Text-to-Speech. Gunakan Chrome, Firefox, atau Edge terbaru.');
        }
        
        // Initialize audio system
        console.log('🔧 Calling QueueAudio.initializeAudio()...');
        const success = await window.QueueAudio.initializeAudio();
        
        if (success) {
            audioInitialized = true;
            console.log('✅ Audio initialization successful');
            
            // Update UI - Hide banner
            if (banner) {
                banner.style.transition = 'all 0.5s ease-out';
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 500);
            }
            
            // Update tips
            if (tips) {
                tips.innerHTML = '✅ Audio aktif! Sekarang Anda bisa menggunakan tombol "Panggil" pada antrian atau test dengan tombol di atas';
                tips.className = tips.className.replace('text-gray-500', 'text-green-600');
            }
            
            // Success notification
            showNotification('✅ Audio berhasil diaktifkan!', 'success');
            
            // Test audio after successful init
            setTimeout(async () => {
                try {
                    await window.QueueAudio.playQueueAudio('Audio berhasil diaktifkan, sistem siap digunakan');
                } catch (error) {
                    console.warn('⚠️ Test audio failed:', error);
                }
            }, 1000);
            
        } else {
            throw new Error('Inisialisasi audio gagal tanpa error spesifik');
        }
        
    } catch (error) {
        console.error('❌ Audio initialization error:', error);
        
        // Detailed error handling
        let errorMsg = 'Gagal mengaktifkan audio:\n\n';
        let solutions = 'Solusi yang bisa dicoba:\n';
        
        if (error.message.includes('tidak dimuat')) {
            errorMsg += '• Script audio belum dimuat\n';
            solutions += '• Refresh halaman (Ctrl+F5)\n';
            solutions += '• Pastikan koneksi internet stabil\n';
        } else if (error.message.includes('tidak mendukung')) {
            errorMsg += '• Browser tidak mendukung Text-to-Speech\n';
            solutions += '• Gunakan Chrome, Firefox, atau Edge terbaru\n';
            solutions += '• Update browser ke versi terbaru\n';
        } else if (error.message.includes('timeout')) {
            errorMsg += '• Timeout saat memuat voices\n';
            solutions += '• Coba tunggu beberapa detik dan ulangi\n';
            solutions += '• Restart browser jika perlu\n';
        } else {
            errorMsg += `• ${error.message}\n`;
            solutions += '• Coba refresh halaman\n';
            solutions += '• Restart browser\n';
            solutions += '• Pastikan audio device berfungsi\n';
        }
        
        // Show error
        showNotification('❌ ' + error.message, 'error');
        alert(errorMsg + '\n' + solutions);
        
        // Reset button
        if (button) {
            button.disabled = false;
            button.textContent = 'Coba Lagi';
            button.className = button.className.replace('bg-gray-400', 'bg-red-500');
        }
    } finally {
        initializationInProgress = false;
        updateAudioStatus();
    }
}

/**
 * Update status display dengan informasi lengkap
 */
function updateAudioStatus() {
    const statusDiv = document.getElementById('audio-status');
    if (!statusDiv) return;
    
    try {
        if (window.QueueAudio) {
            const status = window.QueueAudio.getStatus();
            const speechStatus = status.speechSynthesisStatus || {};
            
            // Create detailed status display
            statusDiv.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                    <div>
                        <strong class="text-blue-600">🎵 Audio System:</strong><br>
                        Manual Init: ${audioInitialized ? '✅ Yes' : '❌ No'}<br>
                        System Init: ${status.isInitialized ? '✅ Yes' : '❌ No'}<br>
                        Currently Playing: ${status.isPlaying ? '🔊 Yes' : '🔇 No'}<br>
                        Last Message: ${status.lastMessage ? '✅ Available' : '❌ None'}
                    </div>
                    <div>
                        <strong class="text-green-600">🌐 Browser Support:</strong><br>
                        Speech API: ${status.speechSynthesisSupported ? '✅ Supported' : '❌ Not Supported'}<br>
                        Livewire: ${status.livewireAvailable ? '✅ Available' : '❌ Not Available'}<br>
                        Voices: ${status.voicesCount} available<br>
                        Document: ${status.documentReady} 
                    </div>
                    <div>
                        <strong class="text-purple-600">🎤 Speech Engine:</strong><br>
                        Speaking: ${speechStatus.speaking ? '✅ Yes' : '❌ No'}<br>
                        Pending: ${speechStatus.pending ? '⏳ Yes' : '✅ No'}<br>
                        Paused: ${speechStatus.paused ? '⏸️ Yes' : '▶️ No'}<br>
                        Voice: ${status.preferredVoice || 'Default'}
                    </div>
                </div>
                ${status.lastPlayTime ? `<div class="mt-2 text-gray-500">Last played: ${status.lastPlayTime}</div>` : ''}
            `;
            
            // Color coding based on status
            if (audioInitialized && status.isInitialized) {
                statusDiv.className = statusDiv.className.replace(/bg-\w+-\d+/, 'bg-green-50');
                statusDiv.style.border = '1px solid #10b981';
            } else {
                statusDiv.className = statusDiv.className.replace(/bg-\w+-\d+/, 'bg-red-50');
                statusDiv.style.border = '1px solid #ef4444';
            }
            
        } else {
            statusDiv.innerHTML = '<span class="text-red-600">❌ QueueAudio script tidak dimuat. Silakan refresh halaman.</span>';
            statusDiv.className = statusDiv.className.replace(/bg-\w+-\d+/, 'bg-red-50');
        }
    } catch (error) {
        statusDiv.innerHTML = `<span class="text-red-600">❌ Error getting status: ${error.message}</span>`;
        console.error('Error updating audio status:', error);
    }
}

/**
 * Test multiple audio calls
 */
function testMultipleAudio() {
    console.log('🔥 Testing multiple audio calls...');
    
    if (!audioInitialized || !window.QueueAudio || !window.QueueAudio.isInitialized) {
        alert('Silakan aktifkan audio terlebih dahulu dengan klik tombol "Aktifkan Audio"!');
        return;
    }
    
    const messages = [
        'Test nomor antrian A001 silakan ke ruang periksa',
        'Test nomor antrian B002 silakan ke ruang periksa', 
        'Test nomor antrian C003 silakan ke ruang periksa'
    ];
    
    showNotification('🔥 Memulai test audio berurutan...', 'info');
    
    messages.forEach((message, index) => {
        setTimeout(() => {
            console.log(`🔊 Playing test message ${index + 1}/${messages.length}:`, message);
            
            if (window.QueueAudio && window.QueueAudio.isInitialized) {
                window.QueueAudio.playQueueAudio(message);
            } else {
                console.error('❌ Audio system no longer available');
            }
        }, index * 4000); // 4 second delay between each
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.header-notification');
    existing.forEach(el => el.remove());
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = 'header-notification';
    notification.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        ${colors[type] || colors.info};
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        font-size: 14px;
        max-width: 300px;
        animation: slideInRight 0.3s ease-out;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

// ==================== STATUS MONITORING ====================

/**
 * Start monitoring audio status
 */
function startStatusMonitoring() {
    if (statusInterval) clearInterval(statusInterval);
    statusInterval = setInterval(updateAudioStatus, 5000);
    console.log('📊 Status monitoring started');
}

/**
 * Stop monitoring audio status
 */
function stopStatusMonitoring() {
    if (statusInterval) {
        clearInterval(statusInterval);
        statusInterval = null;
        console.log('📊 Status monitoring stopped');
    }
}

// ==================== EVENT HANDLERS ====================

/**
 * Enhanced Livewire event handling
 */
function setupLivewireEventHandling() {
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', (message) => {
            console.log('🔧 Queue-called event received in header:', message);
            
            // Update status immediately
            updateAudioStatus();
            
            // Visual feedback
            const statusDiv = document.getElementById('audio-status');
            if (statusDiv) {
                const originalBorder = statusDiv.style.border;
                const originalBg = statusDiv.style.backgroundColor;
                
                statusDiv.style.border = '2px solid #10b981';
                statusDiv.style.backgroundColor = '#f0fdf4';
                
                setTimeout(() => {
                    statusDiv.style.border = originalBorder;
                    statusDiv.style.backgroundColor = originalBg;
                }, 2000);
            }
            
            showNotification('🔊 Audio event received', 'success');
        });
        
        console.log('✅ Livewire events registered in header');
    } else {
        console.warn('⚠️ Livewire not available for header events');
    }
}

// ==================== INITIALIZATION ====================

/**
 * Initialize everything when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Audio header controls DOM loaded');
    
    // Initial status check dengan delay
    setTimeout(() => {
        updateAudioStatus();
        startStatusMonitoring();
    }, 1000);
    
    // Setup Livewire events
    setupLivewireEventHandling();
    
    // Check if voices are already available (untuk auto-detection)
    setTimeout(() => {
        if (window.speechSynthesis && window.QueueAudio) {
            const voices = speechSynthesis.getVoices();
            if (voices.length > 0) {
                console.log('🎤 Voices available, ready for manual initialization');
                updateAudioStatus();
            }
        }
    }, 2000);
});

/**
 * Livewire initialization event
 */
document.addEventListener('livewire:initialized', () => {
    console.log('🔧 Enhanced Livewire initialized for audio header');
    setupLivewireEventHandling();
});

/**
 * Cleanup on page unload
 */
window.addEventListener('beforeunload', function() {
    stopStatusMonitoring();
    if (window.QueueAudio && window.QueueAudio.isPlaying) {
        window.QueueAudio.stop();
    }
});

// ==================== SESSION-BASED AUDIO ====================

// Handle session-based queue calls (jika ada dari server)
@if(session('queue_called'))
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            try {
                const queueData = @json(session('queue_called'));
                console.log('🎯 Processing session queue data:', queueData);
                
                if (queueData && window.QueueAudio && audioInitialized && window.QueueAudio.isInitialized) {
                    let message = '';
                    
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
                    showNotification('🔊 Session audio triggered', 'info');
                } else if (queueData) {
                    console.log('⚠️ Audio not initialized, session queue will not play');
                    showNotification('⚠️ Audio belum aktif, aktivasi dulu', 'warning');
                }
            } catch (error) {
                console.error('❌ Session audio error:', error);
            }
        }, 3000); // Wait for audio to be ready
    });
    
    @php(session()->forget('queue_called'))
@endif

// ==================== GLOBAL ERROR HANDLER ====================

// Global error handler untuk speech-related errors
window.addEventListener('error', function(e) {
    if (e.message && e.message.toLowerCase().includes('speech')) {
        console.warn('🔊 Global speech error intercepted:', e.message);
        showNotification('⚠️ Speech error detected', 'warning');
    }
});

console.log('🔧 Audio header controls loaded and ready');
console.log('📋 Functions: initializeAudio(), testMultipleAudio(), updateAudioStatus()');
</script>