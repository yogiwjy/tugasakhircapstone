{{-- Debug: tampilkan jika ada session --}}
@if(session('queue_called'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 rounded">
        <strong>🔊 Memanggil Antrian:</strong> {{ session('queue_called.number') }}
        <br>
        <small>{{ session('queue_called.message') }}</small>
    </div>
@endif

@if(session('queue_called'))
    <script>
        console.log('=== QUEUE CALL TRIGGERED ===');
        console.log('Session data:', @json(session('queue_called')));
        
        async function playQueueAudio(message) {
            try {
                console.log('🔊 Playing audio:', message);
                
                if (!('speechSynthesis' in window)) {
                    console.error('❌ Speech synthesis not supported');
                    return;
                }
                
                // Cancel any ongoing speech
                window.speechSynthesis.cancel();
                
                // Wait a bit for cancel to complete
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Create utterance
                const utterance = new SpeechSynthesisUtterance(message);
                
                // Get voices
                let voices = window.speechSynthesis.getVoices();
                console.log('Available voices:', voices.length);
                
                if (voices.length === 0) {
                    console.log('⏳ Waiting for voices to load...');
                    await new Promise((resolve) => {
                        window.speechSynthesis.onvoiceschanged = () => {
                            voices = window.speechSynthesis.getVoices();
                            console.log('✅ Voices loaded:', voices.length);
                            resolve();
                        };
                        // Timeout after 2 seconds
                        setTimeout(resolve, 2000);
                    });
                    voices = window.speechSynthesis.getVoices();
                }
                
                // Find Indonesian voice
                const indonesianVoices = voices.filter(voice => 
                    voice.lang.toLowerCase().includes('id') || 
                    voice.name.toLowerCase().includes('indonesia')
                );
                
                console.log('🇮🇩 Indonesian voices found:', indonesianVoices.length);
                
                if (indonesianVoices.length > 0) {
                    utterance.voice = indonesianVoices[0];
                    console.log('🎤 Using voice:', indonesianVoices[0].name);
                }
                
                // Set properties
                utterance.rate = 0.8;
                utterance.pitch = 1.0;
                utterance.volume = 1.0;
                utterance.lang = 'id-ID';
                
                // Event listeners
                utterance.onstart = () => console.log('▶️ Speech started');
                utterance.onend = () => console.log('⏹️ Speech ended');
                utterance.onerror = (event) => console.error('❌ Speech error:', event.error);
                
                // Speak
                console.log('🚀 Starting speech...');
                window.speechSynthesis.speak(utterance);
                
            } catch (error) {
                console.error('💥 Error in playQueueAudio:', error);
            }
        }
        
        // Execute immediately
        document.addEventListener('DOMContentLoaded', function() {
            const queueData = @json(session('queue_called'));
            console.log('📢 Queue data received:', queueData);
            
            if (queueData && queueData.message) {
                console.log('🎯 Triggering audio for:', queueData.number);
                
                // Try immediate execution
                playQueueAudio(queueData.message);
                
                // Also try with delay
                setTimeout(() => {
                    console.log('🔄 Retry with delay...');
                    playQueueAudio(queueData.message);
                }, 1000);
            } else {
                console.log('⚠️ No queue data found');
            }
        });
        
        // If DOM already loaded
        if (document.readyState !== 'loading') {
            const queueData = @json(session('queue_called'));
            if (queueData && queueData.message) {
                console.log('🎯 DOM ready, triggering audio for:', queueData.number);
                setTimeout(() => playQueueAudio(queueData.message), 500);
            }
        }
    </script>
    
    @php(session()->forget('queue_called'))
@endif

{{-- Global test function --}}
<script>
    window.testQueueCall = function(message = 'Test nomor antrian A001 silakan menuju ruang periksa') {
        console.log('🧪 Testing queue call:', message);
        
        if (typeof playQueueAudio === 'function') {
            playQueueAudio(message);
        } else if (typeof playQueueSound === 'function') {
            playQueueSound(message);
        } else {
            console.error('❌ No audio function found');
        }
    };
</script>