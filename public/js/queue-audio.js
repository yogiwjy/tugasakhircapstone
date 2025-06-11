console.log('🎵 Loading Improved Queue Audio Handler...');

// Improved Queue Audio Handler with reliability fixes
window.QueueAudio = {
    isPlaying: false,
    lastMessage: '',
    lastPlayTime: 0,
    retryCount: 0,
    maxRetries: 3,
    audioInitialized: false,

    // Main audio function with debouncing and retry
    async playQueueAudio(message) {
        const now = Date.now();
        
        console.log('🔊 playQueueAudio called:', message);
        
        // Debounce: prevent duplicate calls within 2 seconds
        if (this.lastMessage === message && (now - this.lastPlayTime) < 2000) {
            console.log('🚫 Duplicate call ignored - debouncing');
            return;
        }

        // If already playing, queue the new message
        if (this.isPlaying) {
            console.log('⏳ Audio already playing, queuing new message');
            setTimeout(() => this.playQueueAudio(message), 1000);
            return;
        }

        this.lastMessage = message;
        this.lastPlayTime = now;
        
        // Try multiple methods for maximum compatibility
        const success = await this.tryMultipleMethods(message);
        
        if (!success) {
            console.log('❌ All audio methods failed, showing fallback');
            this.showFallbackAlert(message);
        }
    },

    // Initialize audio with user interaction
    async initializeAudio() {
        console.log('🎵 Initializing audio with user interaction...');
        
        if (!('speechSynthesis' in window)) {
            console.error('❌ Speech synthesis not supported');
            return false;
        }

        try {
            // Create a silent utterance to initialize
            const testUtterance = new SpeechSynthesisUtterance('');
            testUtterance.volume = 0;
            window.speechSynthesis.speak(testUtterance);
            
            // Wait and load voices
            await this.getVoicesWithTimeout();
            
            this.audioInitialized = true;
            console.log('✅ Audio initialized successfully');
            
            // Test with actual sound
            setTimeout(() => {
                this.playQueueAudio('Audio berhasil diaktifkan');
            }, 500);
            
            return true;
        } catch (error) {
            console.error('❌ Audio initialization failed:', error);
            return false;
        }
    },

    // Try different audio methods
    async tryMultipleMethods(message) {
        const methods = [
            () => this.speechSynthesisMethod(message),
            () => this.speechSynthesisMethodAlt(message)
        ];

        for (let i = 0; i < methods.length; i++) {
            try {
                console.log(`🔄 Trying method ${i + 1}`);
                const success = await methods[i]();
                if (success) {
                    console.log(`✅ Method ${i + 1} succeeded`);
                    return true;
                }
            } catch (error) {
                console.log(`❌ Method ${i + 1} failed:`, error);
            }
        }
        
        return false;
    },

    // Primary speech synthesis method
    async speechSynthesisMethod(message) {
        return new Promise(async (resolve) => {
            try {
                if (!('speechSynthesis' in window)) {
                    resolve(false);
                    return;
                }

                this.isPlaying = true;
                this.showAudioIndicator();

                // Force stop any existing speech
                window.speechSynthesis.cancel();
                await this.wait(200);

                // Get voices
                const voices = await this.getVoicesWithTimeout();
                
                // Create utterance
                const utterance = new SpeechSynthesisUtterance(message);
                
                // Configure utterance
                const bestVoice = this.findBestVoice(voices);
                if (bestVoice) {
                    utterance.voice = bestVoice;
                    console.log('🎤 Using voice:', bestVoice.name);
                }
                
                utterance.rate = 0.8;
                utterance.pitch = 1.0;
                utterance.volume = 1.0;
                utterance.lang = 'id-ID';

                let resolved = false;

                // Set up event handlers
                utterance.onstart = () => {
                    console.log('▶️ Speech started');
                };

                utterance.onend = () => {
                    console.log('⏹️ Speech ended successfully');
                    this.isPlaying = false;
                    this.hideAudioIndicator();
                    if (!resolved) {
                        resolved = true;
                        resolve(true);
                    }
                };

                utterance.onerror = (event) => {
                    console.log('❌ Speech error:', event.error);
                    this.isPlaying = false;
                    this.hideAudioIndicator();
                    if (!resolved) {
                        resolved = true;
                        resolve(false);
                    }
                };

                // Timeout fallback
                setTimeout(() => {
                    if (!resolved) {
                        console.log('⏰ Speech timeout');
                        window.speechSynthesis.cancel();
                        this.isPlaying = false;
                        this.hideAudioIndicator();
                        resolved = true;
                        resolve(false);
                    }
                }, 8000); // 8 second timeout

                // Start speaking
                console.log('🚀 Starting speech...');
                window.speechSynthesis.speak(utterance);

                // Check if speaking started
                setTimeout(() => {
                    if (!window.speechSynthesis.speaking && !resolved) {
                        console.log('⚠️ Speech did not start');
                        this.isPlaying = false;
                        this.hideAudioIndicator();
                        resolved = true;
                        resolve(false);
                    }
                }, 1000);

            } catch (error) {
                console.error('💥 Speech synthesis error:', error);
                this.isPlaying = false;
                this.hideAudioIndicator();
                resolve(false);
            }
        });
    },

    // Alternative speech synthesis method (chunked)
    async speechSynthesisMethodAlt(message) {
        return new Promise(async (resolve) => {
            try {
                console.log('🔄 Trying chunked speech method');
                
                this.isPlaying = true;
                this.showAudioIndicator();

                // Split into shorter chunks
                const chunks = this.splitMessage(message);
                let chunkIndex = 0;
                
                const speakChunk = () => {
                    if (chunkIndex >= chunks.length) {
                        this.isPlaying = false;
                        this.hideAudioIndicator();
                        resolve(true);
                        return;
                    }
                    
                    const utterance = new SpeechSynthesisUtterance(chunks[chunkIndex]);
                    utterance.rate = 0.8;
                    utterance.volume = 1.0;
                    utterance.lang = 'id-ID';
                    
                    utterance.onend = () => {
                        chunkIndex++;
                        setTimeout(speakChunk, 200);
                    };
                    
                    utterance.onerror = () => {
                        this.isPlaying = false;
                        this.hideAudioIndicator();
                        resolve(false);
                    };
                    
                    window.speechSynthesis.speak(utterance);
                };
                
                speakChunk();
                
            } catch (error) {
                this.isPlaying = false;
                this.hideAudioIndicator();
                resolve(false);
            }
        });
    },

    // Split message into chunks
    splitMessage(message) {
        const words = message.split(' ');
        const chunks = [];
        
        for (let i = 0; i < words.length; i += 4) {
            chunks.push(words.slice(i, i + 4).join(' '));
        }
        
        return chunks;
    },

    // Get voices with timeout protection
    async getVoicesWithTimeout() {
        return new Promise((resolve) => {
            let voices = window.speechSynthesis.getVoices();
            
            if (voices.length > 0) {
                console.log('🎤 Voices already available:', voices.length);
                resolve(voices);
                return;
            }
            
            console.log('⏳ Waiting for voices...');
            
            const timeout = setTimeout(() => {
                voices = window.speechSynthesis.getVoices();
                console.log('⏰ Voice loading timeout, got:', voices.length);
                resolve(voices);
            }, 3000);
            
            window.speechSynthesis.onvoiceschanged = () => {
                clearTimeout(timeout);
                voices = window.speechSynthesis.getVoices();
                console.log('✅ Voices loaded:', voices.length);
                resolve(voices);
            };
        });
    },

    // Find best voice with preferences
    findBestVoice(voices) {
        if (!voices || voices.length === 0) return null;
        
        // Priority 1: Indonesian local voices
        let indonesianVoices = voices.filter(voice => 
            voice.lang.toLowerCase().includes('id') && voice.localService
        );
        
        if (indonesianVoices.length > 0) {
            console.log('🇮🇩 Using local Indonesian voice');
            return indonesianVoices[0];
        }
        
        // Priority 2: Any Indonesian voice
        indonesianVoices = voices.filter(voice => 
            voice.lang.toLowerCase().includes('id')
        );
        
        if (indonesianVoices.length > 0) {
            console.log('🇮🇩 Using Indonesian voice');
            return indonesianVoices[0];
        }
        
        // Priority 3: Local English voices
        const localEnglishVoices = voices.filter(voice => 
            voice.lang.toLowerCase().includes('en') && voice.localService
        );
        
        if (localEnglishVoices.length > 0) {
            console.log('🇺🇸 Using local English voice');
            return localEnglishVoices[0];
        }
        
        // Priority 4: Any local voice
        const localVoices = voices.filter(voice => voice.localService);
        if (localVoices.length > 0) {
            console.log('💻 Using local voice');
            return localVoices[0];
        }
        
        // Last resort: first available
        console.log('🌐 Using first available voice');
        return voices[0];
    },

    // Utility functions
    wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    showAudioIndicator() {
        let indicator = document.getElementById('queue-audio-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'queue-audio-indicator';
            indicator.innerHTML = `
                <div style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #10b981;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 500;
                    font-size: 14px;
                    animation: pulse 2s infinite;
                ">
                    <div style="
                        width: 12px;
                        height: 12px;
                        background: white;
                        border-radius: 50%;
                        animation: blink 1s infinite;
                    "></div>
                    🔊 Memanggil Antrian...
                </div>
                <style>
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.8; }
                    }
                    @keyframes blink {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.3; }
                    }
                </style>
            `;
            document.body.appendChild(indicator);
        } else {
            indicator.style.display = 'block';
        }
    },

    hideAudioIndicator() {
        const indicator = document.getElementById('queue-audio-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    },

    showFallbackAlert(message) {
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = `
            <div style="
                position: fixed;
                top: 80px;
                right: 20px;
                background: #f59e0b;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                max-width: 350px;
                font-size: 14px;
                font-weight: 500;
                animation: slideIn 0.3s ease-out;
            ">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 18px;">📢</span>
                    <strong>PANGGILAN ANTRIAN</strong>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 8px; border-radius: 4px;">
                    ${message}
                </div>
            </div>
            <style>
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            </style>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto remove with fade effect
        setTimeout(() => {
            if (document.body.contains(alertDiv)) {
                alertDiv.style.transition = 'opacity 0.5s, transform 0.5s';
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(alertDiv)) {
                        document.body.removeChild(alertDiv);
                    }
                }, 500);
            }
        }, 5000);
    },

    // Public methods
    test(message = 'Test nomor antrian A001 silakan menuju ruang periksa') {
        console.log('🧪 Testing queue audio');
        this.playQueueAudio(message);
    },

    stop() {
        console.log('🛑 Stopping all audio');
        window.speechSynthesis.cancel();
        this.isPlaying = false;
        this.hideAudioIndicator();
    },

    // Get status for debugging
    getStatus() {
        return {
            isPlaying: this.isPlaying,
            lastMessage: this.lastMessage,
            lastPlayTime: this.lastPlayTime,
            audioInitialized: this.audioInitialized,
            speechSynthesisSupported: 'speechSynthesis' in window,
            speechSynthesisStatus: window.speechSynthesis ? {
                speaking: window.speechSynthesis.speaking,
                pending: window.speechSynthesis.pending,
                paused: window.speechSynthesis.paused
            } : null
        };
    }
};

// Global shortcuts
window.testQueueCall = function(message) {
    window.QueueAudio.test(message);
};

window.stopQueueAudio = function() {
    window.QueueAudio.stop();
};

window.getAudioStatus = function() {
    const status = window.QueueAudio.getStatus();
    console.log('Audio Status:', status);
    return status;
};

window.initializeAudio = function() {
    return window.QueueAudio.initializeAudio();
};

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎵 Queue Audio Handler DOM ready');
    
    // Pre-load voices if available
    if ('speechSynthesis' in window) {
        window.QueueAudio.getVoicesWithTimeout().then(voices => {
            console.log(`🎤 ${voices.length} voices available`);
        });
    }
});

// Livewire integration with event deduplication
let lastLivewireEvent = '';
let lastLivewireTime = 0;

document.addEventListener('livewire:initialized', () => {
    console.log('🔌 Livewire initialized - Queue audio integration ready');
    
    Livewire.on('queue-called', (message) => {
        const now = Date.now();
        
        // Deduplicate Livewire events
        if (message === lastLivewireEvent && (now - lastLivewireTime) < 1500) {
            console.log('🚫 Duplicate Livewire event ignored');
            return;
        }
        
        lastLivewireEvent = message;
        lastLivewireTime = now;
        
        console.log('📡 Livewire queue-called:', message);
        window.QueueAudio.playQueueAudio(message);
    });
});

console.log('✅ Queue Audio Handler loaded successfully');