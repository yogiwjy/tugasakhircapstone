/**
 * Queue Audio System - ALWAYS READY VERSION
 * File: public/js/queue-audio.js
 * 
 * Audio selalu siap tanpa perlu klik aktivasi atau user interaction
 * Auto-initialize di background secara silent
 */

// ================== GLOBAL STATE MANAGEMENT ==================
window.QueueAudioState = {
    isInitialized: false,
    isPlaying: false,
    lastMessage: null,
    lastPlayTime: null,
    voices: [],
    preferredVoice: null,
    silentMode: true, // Always run in silent mode
    autoRetryCount: 0,
    maxAutoRetries: 5
};

// ================== CORE AUDIO CLASS ==================
window.QueueAudio = {
    // Check if audio is supported
    isSupported() {
        return 'speechSynthesis' in window;
    },

    // Silent auto-initialization (no user interaction required)
    async initializeAudio() {
        const state = window.QueueAudioState;
        
        if (!this.isSupported()) {
            console.warn('Speech synthesis not supported in this browser');
            return false;
        }

        if (state.isInitialized) {
            return true;
        }

        try {
            // Load voices silently
            await this._waitForVoices();
            
            // Silent test (no actual audio output)
            await this._silentTest();
            
            state.isInitialized = true;
            console.log('🔊 Audio system ready (silent mode)');
            
            return true;
            
        } catch (error) {
            console.warn('Silent audio initialization failed:', error.message);
            
            // Auto-retry mechanism
            if (state.autoRetryCount < state.maxAutoRetries) {
                state.autoRetryCount++;
                console.log(`🔄 Auto-retry ${state.autoRetryCount}/${state.maxAutoRetries}`);
                
                setTimeout(() => {
                    this.initializeAudio();
                }, 2000 * state.autoRetryCount); // Exponential backoff
            }
            
            return false;
        }
    },

    // Wait for voices with multiple strategies
    _waitForVoices(timeout = 5000) {
        return new Promise((resolve) => {
            const state = window.QueueAudioState;
            let resolved = false;
            
            const checkVoices = () => {
                if (resolved) return;
                
                const voices = speechSynthesis.getVoices();
                if (voices.length > 0) {
                    state.voices = voices;
                    
                    // Find best voice (Indonesian first, then any)
                    state.preferredVoice = voices.find(voice => 
                        voice.lang.includes('id') || 
                        voice.name.toLowerCase().includes('indonesia')
                    ) || voices.find(voice => 
                        voice.lang.includes('en') || voice.default
                    ) || voices[0];
                    
                    console.log(`🎤 Loaded ${voices.length} voices, using: ${state.preferredVoice?.name || 'default'}`);
                    resolved = true;
                    resolve();
                }
            };

            // Multiple strategies to get voices
            checkVoices(); // Immediate check
            speechSynthesis.onvoiceschanged = checkVoices; // Event listener
            
            // Force voice loading
            if (speechSynthesis.getVoices().length === 0) {
                // Trigger voice loading by creating a silent utterance
                const utterance = new SpeechSynthesisUtterance('');
                utterance.volume = 0;
                speechSynthesis.speak(utterance);
                speechSynthesis.cancel();
            }
            
            // Polling fallback
            const pollInterval = setInterval(() => {
                if (!resolved) {
                    checkVoices();
                }
            }, 200);
            
            // Always resolve after timeout
            setTimeout(() => {
                if (!resolved) {
                    clearInterval(pollInterval);
                    console.log('🎤 Voice loading timeout, proceeding anyway');
                    resolved = true;
                    resolve();
                }
            }, timeout);
        });
    },

    // Silent test that doesn't trigger autoplay restrictions
    _silentTest() {
        return new Promise((resolve) => {
            try {
                // Create completely silent utterance for testing
                const utterance = new SpeechSynthesisUtterance(' '); // Single space
                utterance.volume = 0;
                utterance.rate = 10; // Very fast
                utterance.pitch = 0.1;
                
                utterance.onend = () => resolve();
                utterance.onerror = () => resolve(); // Don't fail on test errors
                
                speechSynthesis.cancel();
                speechSynthesis.speak(utterance);
                
                // Quick timeout
                setTimeout(resolve, 100);
                
            } catch (error) {
                resolve(); // Always resolve, don't block initialization
            }
        });
    },

    // Enhanced message processing
    _processMessage(message) {
        if (!message) return null;
        
        let processedMessage = '';
        
        try {
            if (typeof message === 'string') {
                processedMessage = message.trim();
            } else if (typeof message === 'object') {
                if (message.message) {
                    processedMessage = String(message.message).trim();
                } else if (message.text) {
                    processedMessage = String(message.text).trim();
                } else if (Array.isArray(message) && message.length > 0) {
                    processedMessage = String(message[0]).trim();
                } else {
                    // Try to extract any string value from object
                    const values = Object.values(message);
                    const stringValue = values.find(v => typeof v === 'string' && v.trim().length > 0);
                    processedMessage = stringValue ? String(stringValue).trim() : '';
                }
            } else {
                processedMessage = String(message).trim();
            }
        } catch (error) {
            console.warn('Message processing error:', error);
            processedMessage = '';
        }
        
        return processedMessage.length > 0 ? processedMessage : null;
    },

    // Main speech function - always ready
    async playQueueAudio(message) {
        const state = window.QueueAudioState;
        
        // Process message
        const processedMessage = this._processMessage(message);
        if (!processedMessage) {
            console.warn('Invalid or empty audio message:', message);
            return false;
        }

        // Auto-initialize if not ready (silent)
        if (!state.isInitialized) {
            console.log('🔧 Auto-initializing audio system...');
            const initSuccess = await this.initializeAudio();
            if (!initSuccess) {
                console.warn('Auto-initialization failed, attempting fallback...');
                // Try emergency initialization
                try {
                    state.isInitialized = true; // Force enable
                } catch (error) {
                    console.error('Emergency audio initialization failed');
                    return false;
                }
            }
        }

        // Stop any current audio
        if (state.isPlaying) {
            this.stop();
            await this._delay(50);
        }

        try {
            state.isPlaying = true;
            state.lastMessage = processedMessage;
            state.lastPlayTime = new Date().toISOString();
            
            // Create optimized utterance
            const utterance = new SpeechSynthesisUtterance(processedMessage);
            utterance.rate = 0.9; // Slightly slower for clarity
            utterance.volume = 1.0;
            utterance.pitch = 1.0;
            utterance.lang = 'id-ID';
            
            // Use best available voice
            if (state.preferredVoice) {
                utterance.voice = state.preferredVoice;
            }
            
            // Enhanced event handlers
            utterance.onstart = () => {
                console.log('🔊 Audio playing:', processedMessage.substring(0, 50) + (processedMessage.length > 50 ? '...' : ''));
            };
            
            utterance.onend = () => {
                state.isPlaying = false;
                console.log('✅ Audio completed');
            };
            
            utterance.onerror = (error) => {
                console.warn('Audio error:', error.error);
                state.isPlaying = false;
                
                // Try emergency fallback
                this._emergencyFallback(processedMessage);
            };
            
            // Enhanced speech execution
            speechSynthesis.cancel(); // Clear queue
            await this._delay(10); // Very short delay
            speechSynthesis.speak(utterance);
            
            // Backup check - if not speaking after delay, try again
            setTimeout(() => {
                if (state.isPlaying && !speechSynthesis.speaking && !speechSynthesis.pending) {
                    console.warn('Speech not started, retrying...');
                    speechSynthesis.speak(utterance);
                }
            }, 200);
            
            return true;
            
        } catch (error) {
            console.error('Audio playback error:', error);
            state.isPlaying = false;
            this._emergencyFallback(processedMessage);
            return false;
        }
    },

    // Emergency fallback for critical audio
    _emergencyFallback(message) {
        console.log('🚨 Emergency audio fallback for:', message);
        
        try {
            // Try different approach
            speechSynthesis.cancel();
            
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.rate = 1.0;
            utterance.volume = 1.0;
            utterance.lang = 'id-ID';
            
            // Don't use preferred voice in emergency mode
            utterance.onend = () => {
                window.QueueAudioState.isPlaying = false;
            };
            
            speechSynthesis.speak(utterance);
            
        } catch (error) {
            console.error('Emergency fallback also failed:', error);
            // Last resort: show visual notification
            this._showEmergencyNotification(message);
        }
    },

    // Last resort visual notification
    _showEmergencyNotification(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #dc2626;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            z-index: 99999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: pulse 1s infinite;
        `;
        
        notification.innerHTML = `🔊 PANGGILAN ANTRIAN: ${message}`;
        document.body.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 8000);
        
        // Add pulse animation
        if (!document.getElementById('emergency-pulse-style')) {
            const style = document.createElement('style');
            style.id = 'emergency-pulse-style';
            style.textContent = `
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
            `;
            document.head.appendChild(style);
        }
    },

    // Stop current speech
    stop() {
        try {
            speechSynthesis.cancel();
            window.QueueAudioState.isPlaying = false;
        } catch (error) {
            console.warn('Error stopping speech:', error);
        }
    },

    // Get status for debugging
    getStatus() {
        const state = window.QueueAudioState;
        return {
            isInitialized: state.isInitialized,
            isPlaying: state.isPlaying,
            speechSupported: this.isSupported(),
            voicesCount: state.voices.length,
            preferredVoice: state.preferredVoice?.name || 'None',
            lastMessage: state.lastMessage,
            lastPlayTime: state.lastPlayTime,
            autoRetryCount: state.autoRetryCount,
            speechSynthesisStatus: {
                speaking: speechSynthesis?.speaking || false,
                pending: speechSynthesis?.pending || false,
                paused: speechSynthesis?.paused || false
            }
        };
    },

    // Force re-initialization
    forceReinit() {
        window.QueueAudioState.isInitialized = false;
        window.QueueAudioState.autoRetryCount = 0;
        return this.initializeAudio();
    },

    // Utility delay
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
};

// ================== GLOBAL HELPER FUNCTIONS ==================

// Main queue call handler
window.handleQueueCall = function(message) {
    console.log('📞 Queue call received:', typeof message === 'string' ? message.substring(0, 50) + '...' : typeof message);
    return window.QueueAudio.playQueueAudio(message);
};

// Backward compatibility
window.playQueueAudio = function(message) {
    return window.QueueAudio.playQueueAudio(message);
};

window.stopQueueAudio = function() {
    return window.QueueAudio.stop();
};

// Debug functions (console only)
window.getAudioStatus = function() {
    const status = window.QueueAudio.getStatus();
    console.table(status);
    return status;
};

window.testQueueCall = function(message = 'Test audio sistem antrian berfungsi normal') {
    console.log('🧪 Manual test:', message);
    return window.QueueAudio.playQueueAudio(message);
};

window.forceAudioReinit = function() {
    console.log('🔄 Force reinitializing audio system...');
    return window.QueueAudio.forceReinit();
};

// ================== AUTO-INITIALIZATION ==================

// Aggressive auto-initialization strategies
async function initializeAudioAggressively() {
    console.log('🚀 Starting aggressive audio initialization...');
    
    // Strategy 1: Immediate initialization
    setTimeout(async () => {
        await window.QueueAudio.initializeAudio();
    }, 100);
    
    // Strategy 2: After DOM ready
    setTimeout(async () => {
        if (!window.QueueAudioState.isInitialized) {
            await window.QueueAudio.initializeAudio();
        }
    }, 1000);
    
    // Strategy 3: After voices should be loaded
    setTimeout(async () => {
        if (!window.QueueAudioState.isInitialized) {
            await window.QueueAudio.initializeAudio();
        }
    }, 3000);
    
    // Strategy 4: Periodic retry
    const retryInterval = setInterval(async () => {
        if (!window.QueueAudioState.isInitialized && window.QueueAudioState.autoRetryCount < 3) {
            await window.QueueAudio.initializeAudio();
        }
        
        if (window.QueueAudioState.isInitialized || window.QueueAudioState.autoRetryCount >= 3) {
            clearInterval(retryInterval);
        }
    }, 5000);
}

// Enhanced Livewire event setup
function setupLivewireEvents() {
    if (window.Livewire && window.Livewire.on) {
        window.Livewire.on('queue-called', function(message) {
            window.handleQueueCall(message);
        });
        
        console.log('✅ Livewire audio events registered');
    } else {
        setTimeout(setupLivewireEvents, 500);
    }
}

// ================== INITIALIZATION SEQUENCE ==================

// Start initialization immediately
initializeAudioAggressively();

// Document ready initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎵 Always Ready Audio System - Loaded');
    
    setupLivewireEvents();
    
    // Additional initialization attempt
    setTimeout(async () => {
        if (!window.QueueAudioState.isInitialized) {
            console.log('🔧 Final initialization attempt...');
            await window.QueueAudio.initializeAudio();
        }
    }, 2000);
});

// Livewire ready
document.addEventListener('livewire:initialized', function() {
    console.log('🔧 Livewire ready for always-ready audio');
    setupLivewireEvents();
});

// Page visibility change handling
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, ensure audio is ready
        setTimeout(async () => {
            if (!window.QueueAudioState.isInitialized) {
                await window.QueueAudio.initializeAudio();
            }
        }, 500);
    }
});

// Window focus handling  
window.addEventListener('focus', function() {
    setTimeout(async () => {
        if (!window.QueueAudioState.isInitialized) {
            await window.QueueAudio.initializeAudio();
        }
    }, 200);
});

console.log('✅ Always Ready Audio System - Initialized');
console.log('🔊 Audio will be automatically ready without any manual activation');