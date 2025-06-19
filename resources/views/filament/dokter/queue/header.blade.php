{{-- File: resources/views/filament/dokter/queue/header.blade.php --}}
{{-- SISTEM AUDIO TERPISAH UNTUK PANEL DOKTER --}}

<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
    <div class="flex items-center gap-3">
        <span class="text-2xl">🎵</span>
        <div>
            <h3 class="font-semibold text-green-800">Audio Panel Dokter</h3>
            <p class="text-sm text-green-700">Sistem audio terpisah untuk panel dokter</p>
        </div>
    </div>
</div>

<script>
// SISTEM AUDIO TERPISAH UNTUK PANEL DOKTER
console.log('🏥 Dokter Panel Audio System - Loading...');

// State terpisah untuk Panel Dokter
window.DokterAudioState = {
    isInitialized: false,
    isPlaying: false,
    lastMessage: null,
    voices: [],
    preferredVoice: null
};

// Sistem Audio untuk Panel Dokter (Terpisah dari Admin)
window.DokterQueueAudio = {
    isSupported() {
        return 'speechSynthesis' in window;
    },

    async initializeAudio() {
        const state = window.DokterAudioState;
        
        if (!this.isSupported()) {
            console.warn('Dokter Panel - Speech synthesis not supported');
            return false;
        }

        if (state.isInitialized) {
            return true;
        }

        try {
            await this._waitForVoices();
            await this._silentTest();
            
            state.isInitialized = true;
            console.log('🔊 Dokter Panel Audio - Ready');
            return true;
            
        } catch (error) {
            console.warn('Dokter audio initialization failed:', error.message);
            return false;
        }
    },

    _waitForVoices(timeout = 5000) {
        return new Promise((resolve) => {
            const state = window.DokterAudioState;
            let resolved = false;
            
            const checkVoices = () => {
                if (resolved) return;
                
                const voices = speechSynthesis.getVoices();
                if (voices.length > 0) {
                    state.voices = voices;
                    state.preferredVoice = voices.find(voice => 
                        voice.lang.includes('id') || voice.name.toLowerCase().includes('indonesia')
                    ) || voices[0];
                    
                    console.log(`🎤 Dokter - Loaded ${voices.length} voices`);
                    resolved = true;
                    resolve();
                }
            };

            checkVoices();
            speechSynthesis.onvoiceschanged = checkVoices;
            
            setTimeout(() => {
                if (!resolved) {
                    resolved = true;
                    resolve();
                }
            }, timeout);
        });
    },

    _silentTest() {
        return new Promise((resolve) => {
            try {
                const utterance = new SpeechSynthesisUtterance(' ');
                utterance.volume = 0;
                utterance.rate = 10;
                utterance.onend = () => resolve();
                utterance.onerror = () => resolve();
                
                speechSynthesis.speak(utterance);
                setTimeout(resolve, 100);
            } catch (error) {
                resolve();
            }
        });
    },

    async playQueueAudio(message) {
        const state = window.DokterAudioState;
        
        if (!message) return false;

        // Process message
        let processedMessage = '';
        if (typeof message === 'string') {
            processedMessage = message;
        } else if (message && message.message) {
            processedMessage = message.message;
        } else if (message && message.text) {
            processedMessage = message.text;
        } else {
            processedMessage = String(message);
        }

        if (!processedMessage.trim()) return false;

        if (!state.isInitialized) {
            await this.initializeAudio();
        }

        try {
            state.isPlaying = true;
            state.lastMessage = processedMessage;
            
            const utterance = new SpeechSynthesisUtterance(processedMessage);
            utterance.rate = 0.9;
            utterance.volume = 1.0;
            utterance.lang = 'id-ID';
            
            if (state.preferredVoice) {
                utterance.voice = state.preferredVoice;
            }
            
            utterance.onstart = () => {
                console.log('🔊 Dokter Panel - Audio playing:', processedMessage.substring(0, 50) + '...');
            };
            
            utterance.onend = () => {
                state.isPlaying = false;
                console.log('✅ Dokter Panel - Audio completed');
            };
            
            utterance.onerror = (error) => {
                console.warn('Dokter Panel Audio error:', error.error);
                state.isPlaying = false;
            };
            
            speechSynthesis.cancel();
            await this._delay(50); // Small delay to ensure cancel is processed
            speechSynthesis.speak(utterance);
            
            return true;
            
        } catch (error) {
            console.error('Dokter Panel Audio error:', error);
            state.isPlaying = false;
            return false;
        }
    },

    stop() {
        try {
            speechSynthesis.cancel();
            window.DokterAudioState.isPlaying = false;
        } catch (error) {
            console.warn('Error stopping dokter audio:', error);
        }
    },

    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
};

// Global functions untuk Dokter Panel (override admin functions)
window.handleQueueCall = function(message) {
    console.log('📞 Dokter Panel - Queue call:', message);
    return window.DokterQueueAudio.playQueueAudio(message);
};

window.playQueueAudio = function(message) {
    return window.DokterQueueAudio.playQueueAudio(message);
};

window.stopQueueAudio = function() {
    return window.DokterQueueAudio.stop();
};

// Test function untuk Dokter Panel
window.testDokterAudio = function(message = 'Test audio panel dokter') {
    return window.DokterQueueAudio.playQueueAudio(message);
};

// Auto-initialize untuk Dokter Panel
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏥 Dokter Panel Audio - Initializing...');
    
    setTimeout(async () => {
        await window.DokterQueueAudio.initializeAudio();
    }, 1000);
});

// Livewire events untuk Dokter Panel
document.addEventListener('livewire:initialized', function() {
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', function(message) {
            window.handleQueueCall(message);
        });
        console.log('✅ Dokter Panel - Livewire events registered');
    }
});

// Setup events dengan retry
function setupDokterAudioEvents() {
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', function(message) {
            window.handleQueueCall(message);
        });
        console.log('✅ Dokter Panel - Audio events ready');
    } else {
        setTimeout(setupDokterAudioEvents, 500);
    }
}

// Initialize events
setupDokterAudioEvents();

console.log('✅ Dokter Panel Audio System - Loaded (Separated from Admin)');
</script>