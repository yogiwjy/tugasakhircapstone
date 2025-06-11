/**
 * Queue Audio System - Fixed Version
 * Handles audio announcements for queue calls with robust error handling
 */

window.QueueAudio = {
    isInitialized: false,
    voices: [],
    preferredVoice: null,
    isPlaying: false,
    lastMessage: null,
    lastPlayTime: null,
    fallbackMethod: 'chunked',
    maxRetries: 3,

    /**
     * Initialize audio system with better error handling
     */
    async initializeAudio() {
        console.log('🎵 Initializing Queue Audio System...');
        
        try {
            // Check speech synthesis support
            if (!('speechSynthesis' in window)) {
                console.error('❌ Speech Synthesis not supported');
                return false;
            }

            // Load voices with timeout and retry
            await this.loadVoicesWithRetry();
            
            // Set up event listeners
            this.setupEventListeners();
            
            this.isInitialized = true;
            console.log('✅ Queue Audio System initialized successfully');
            
            return true;
            
        } catch (error) {
            console.error('❌ Failed to initialize audio:', error);
            return false;
        }
    },

    /**
     * Load voices with retry mechanism
     */
    async loadVoicesWithRetry(retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                this.voices = await this.getVoicesWithTimeout(3000);
                
                if (this.voices.length > 0) {
                    this.selectPreferredVoice();
                    console.log(`🎤 Loaded ${this.voices.length} voices`);
                    return true;
                }
                
                // Wait before retry
                if (i < retries - 1) {
                    console.log(`⏳ Retry ${i + 1}/${retries} loading voices...`);
                    await this.delay(1000);
                }
                
            } catch (error) {
                console.warn(`⚠️ Voice loading attempt ${i + 1} failed:`, error);
            }
        }
        
        console.warn('⚠️ Using default voice (no voices loaded)');
        return false;
    },

    /**
     * Get voices with timeout
     */
    getVoicesWithTimeout(timeout = 5000) {
        return new Promise((resolve, reject) => {
            const timeoutId = setTimeout(() => {
                reject(new Error('Voice loading timeout'));
            }, timeout);

            const getVoices = () => {
                const voices = speechSynthesis.getVoices();
                if (voices.length > 0) {
                    clearTimeout(timeoutId);
                    resolve(voices);
                } else {
                    // Voices not ready yet, wait for voiceschanged event
                    speechSynthesis.addEventListener('voiceschanged', () => {
                        const voicesAgain = speechSynthesis.getVoices();
                        if (voicesAgain.length > 0) {
                            clearTimeout(timeoutId);
                            resolve(voicesAgain);
                        }
                    }, { once: true });
                }
            };

            getVoices();
        });
    },

    /**
     * Select preferred Indonesian voice
     */
    selectPreferredVoice() {
        // Priority order for Indonesian voices
        const preferredNames = [
            'Google Bahasa Indonesia',
            'Indonesian',
            'Bahasa Indonesia',
            'id-ID'
        ];

        // Find exact match first
        for (const name of preferredNames) {
            const voice = this.voices.find(v => 
                v.name.includes(name) || v.lang.includes('id')
            );
            if (voice) {
                this.preferredVoice = voice;
                console.log(`🎤 Using voice: ${voice.name} (${voice.lang})`);
                return;
            }
        }

        // Fallback to any voice with Indonesian language
        const indonesianVoice = this.voices.find(v => 
            v.lang.toLowerCase().includes('id') || 
            v.lang.toLowerCase().includes('indonesia')
        );

        if (indonesianVoice) {
            this.preferredVoice = indonesianVoice;
            console.log(`🎤 Using Indonesian voice: ${indonesianVoice.name}`);
        } else {
            // Last resort - use first available voice
            this.preferredVoice = this.voices[0];
            console.log(`🎤 Using fallback voice: ${this.voices[0].name}`);
        }
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Listen for Livewire events
        document.addEventListener('livewire:initialized', () => {
            console.log('🔧 Queue Audio - Livewire initialized');
            this.setupLivewireListeners();
        });

        // If Livewire already initialized
        if (window.Livewire) {
            this.setupLivewireListeners();
        }

        // Window visibility change handler
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && this.isPlaying) {
                this.stop();
            }
        });
    },

    /**
     * Setup Livewire event listeners
     */
    setupLivewireListeners() {
        if (window.Livewire && window.Livewire.on) {
            window.Livewire.on('queue-called', (event) => {
                console.log('🔊 Queue called event received:', event);
                this.playQueueAudio(event);
            });
        }
    },

    /**
     * Main function to play queue audio
     */
    async playQueueAudio(message) {
        if (!message || typeof message !== 'string') {
            console.warn('⚠️ Invalid message for audio:', message);
            return false;
        }

        console.log('🔊 Playing queue audio:', message);
        
        this.lastMessage = message;
        this.lastPlayTime = Date.now();

        // Stop any current speech
        this.stop();

        // Try multiple methods with fallback
        const methods = [
            () => this.playWithUtterance(message),
            () => this.playChunkedSpeech(message),
            () => this.playSimpleFallback(message)
        ];

        for (let i = 0; i < methods.length; i++) {
            try {
                console.log(`🎵 Trying method ${i + 1}`);
                const success = await methods[i]();
                
                if (success) {
                    console.log(`✅ Audio method ${i + 1} succeeded`);
                    return true;
                }
                
            } catch (error) {
                console.warn(`⚠️ Audio method ${i + 1} failed:`, error);
            }
        }

        console.error('❌ All audio methods failed, showing fallback');
        this.showFallbackNotification(message);
        return false;
    },

    /**
     * Method 1: Standard utterance
     */
    playWithUtterance(message) {
        return new Promise((resolve, reject) => {
            try {
                const utterance = new SpeechSynthesisUtterance(message);
                
                // Configure utterance
                utterance.rate = 0.9;
                utterance.pitch = 1.0;
                utterance.volume = 1.0;
                utterance.lang = 'id-ID';
                
                if (this.preferredVoice) {
                    utterance.voice = this.preferredVoice;
                    console.log(`🎤 Using voice: ${this.preferredVoice.name}`);
                }

                // Set up event handlers
                const timeout = setTimeout(() => {
                    console.log('⏰ Speech timeout');
                    this.isPlaying = false;
                    reject(new Error('Speech timeout'));
                }, 10000);

                utterance.onstart = () => {
                    console.log('🎵 Starting speech...');
                    this.isPlaying = true;
                };

                utterance.onend = () => {
                    console.log('✅ Speech completed');
                    clearTimeout(timeout);
                    this.isPlaying = false;
                    resolve(true);
                };

                utterance.onerror = (event) => {
                    console.error('❌ Speech error:', event.error);
                    clearTimeout(timeout);
                    this.isPlaying = false;
                    reject(new Error(`Speech error: ${event.error}`));
                };

                // Start speech
                speechSynthesis.speak(utterance);
                
            } catch (error) {
                this.isPlaying = false;
                reject(error);
            }
        });
    },

    /**
     * Method 2: Chunked speech for long messages
     */
    async playChunkedSpeech(message) {
        console.log('🎵 Trying chunked speech method');
        
        // Split message into smaller chunks
        const chunks = this.splitMessage(message);
        
        for (let i = 0; i < chunks.length; i++) {
            try {
                await this.playWithUtterance(chunks[i]);
                
                // Small delay between chunks
                if (i < chunks.length - 1) {
                    await this.delay(500);
                }
                
            } catch (error) {
                console.warn(`⚠️ Chunk ${i + 1} failed:`, error);
                throw error;
            }
        }
        
        return true;
    },

    /**
     * Method 3: Simple fallback
     */
    playSimpleFallback(message) {
        return new Promise((resolve, reject) => {
            try {
                // Very basic utterance without advanced settings
                const utterance = new SpeechSynthesisUtterance(message);
                
                utterance.onend = () => {
                    this.isPlaying = false;
                    resolve(true);
                };
                
                utterance.onerror = (event) => {
                    this.isPlaying = false;
                    reject(new Error('Simple fallback failed'));
                };

                this.isPlaying = true;
                speechSynthesis.speak(utterance);
                
            } catch (error) {
                this.isPlaying = false;
                reject(error);
            }
        });
    },

    /**
     * Split long messages into chunks
     */
    splitMessage(message, maxLength = 100) {
        if (message.length <= maxLength) {
            return [message];
        }

        const chunks = [];
        const words = message.split(' ');
        let currentChunk = '';

        for (const word of words) {
            if ((currentChunk + ' ' + word).length <= maxLength) {
                currentChunk += (currentChunk ? ' ' : '') + word;
            } else {
                if (currentChunk) {
                    chunks.push(currentChunk);
                }
                currentChunk = word;
            }
        }

        if (currentChunk) {
            chunks.push(currentChunk);
        }

        return chunks;
    },

    /**
     * Show visual fallback when audio fails
     */
    showFallbackNotification(message) {
        // Create visual notification as fallback
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f59e0b;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            max-width: 400px;
            font-weight: bold;
            animation: slideIn 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">🔊</span>
                <div>
                    <div style="font-size: 14px; margin-bottom: 4px;">PANGGILAN ANTRIAN</div>
                    <div style="font-size: 16px;">${message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: auto;">×</button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 8 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 8000);
    },

    /**
     * Stop current speech
     */
    stop() {
        try {
            if (speechSynthesis.speaking) {
                speechSynthesis.cancel();
            }
            this.isPlaying = false;
        } catch (error) {
            console.warn('⚠️ Error stopping speech:', error);
        }
    },

    /**
     * Get current status
     */
    getStatus() {
        return {
            isInitialized: this.isInitialized,
            audioInitialized: this.isInitialized,
            speechSynthesisSupported: 'speechSynthesis' in window,
            voicesCount: this.voices.length,
            preferredVoice: this.preferredVoice?.name || 'None',
            isPlaying: this.isPlaying,
            lastMessage: this.lastMessage,
            lastPlayTime: this.lastPlayTime,
            speechSynthesisStatus: {
                speaking: speechSynthesis.speaking,
                pending: speechSynthesis.pending,
                paused: speechSynthesis.paused
            }
        };
    },

    /**
     * Utility function for delays
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
};

// Global functions for easy access
window.testQueueCall = function(message = 'Test nomor antrian A001 silakan ke loket 1') {
    console.log('🧪 Testing queue call:', message);
    window.QueueAudio.playQueueAudio(message);
};

window.stopQueueAudio = function() {
    console.log('🛑 Stopping queue audio');
    window.QueueAudio.stop();
};

window.getAudioStatus = function() {
    const status = window.QueueAudio.getStatus();
    console.log('📊 Audio Status:', status);
    return status;
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('📱 DOM ready - Queue Audio available');
    
    // Don't auto-initialize, let user click the button
    setTimeout(() => {
        if (window.speechSynthesis && window.speechSynthesis.getVoices().length > 0) {
            console.log('🎤 Voices already available, ready for manual init');
        }
    }, 1000);
});

console.log('🎵 Queue Audio Script Loaded - Fixed Version');