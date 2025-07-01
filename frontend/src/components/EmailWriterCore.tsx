import { useState } from 'react';
import { Send, Copy, Check, Mail, Sparkles, MessageSquare, User } from 'lucide-react';
import { generateNewEmail } from '../services/api'; // Import the API function

// Renaming the component to EmailWriterCore as it's no longer the main "App"
export default function EmailWriterCore() {
  const [rawThoughts, setRawThoughts] = useState('');
  const [tone, setTone] = useState('professional');
  const [contextEmail, setContextEmail] = useState('');
  const [generatedEmail, setGeneratedEmail] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [copied, setCopied] = useState(false);
  const [showContext, setShowContext] = useState(false);

  const tones = [
    { value: 'professional', label: 'Professional', description: 'Clear and business-appropriate' },
    { value: 'warm', label: 'Warm', description: 'Friendly and approachable' },
    { value: 'concise', label: 'Concise', description: 'Brief and to the point' },
    { value: 'formal', label: 'Formal', description: 'Traditional and respectful' },
    { value: 'casual', label: 'Casual', description: 'Relaxed and conversational' },
    { value: 'persuasive', label: 'Persuasive', description: 'Compelling and convincing' }
  ];

  /**
   * Handles the email generation process.
   * It calls the backend API with the user's input (rawThoughts, tone, contextEmail).
   * Updates the state with the generated email or an error message.
   */
  const generateEmail = async () => {
    if (!rawThoughts.trim()) return; // Do not proceed if rawThoughts is empty

    setIsLoading(true);
    setGeneratedEmail(''); // Clear previous email before new generation
    try {
      const emailData = {
        rawThoughts,
        tone,
        contextEmail: contextEmail.trim() ? contextEmail : undefined, // Only send contextEmail if it has content
      };

      // Call the API service function to generate the email
      const response = await generateNewEmail(emailData);

      if (response.data && response.data.generatedEmail) {
        setGeneratedEmail(response.data.generatedEmail.trim());
      } else {
        // Handle cases where the response might be successful (e.g., 200 OK) but not contain the expected data
        console.error('Error generating email: Unexpected response format from API', response);
        setGeneratedEmail('Sorry, there was an issue with the email generation response. Please check the format and try again.');
      }
    } catch (err: any) {
      // Handle API call errors (network issues, server errors like 4xx, 5xx)
      console.error('Error generating email via API:', err);
      const errorMessage = err.response?.data?.error // Error message from backend
                         || err.message             // Network error or other client-side error message
                         || 'Sorry, there was an error generating your email. Please try again.';
      setGeneratedEmail(errorMessage); // Display the error message in the output area
    } finally {
      setIsLoading(false); // Ensure loading state is reset
    }
  };

  const copyToClipboard = async () => {
    try {
      await navigator.clipboard.writeText(generatedEmail);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error('Failed to copy:', error);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
      generateEmail();
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      {/* Header */}
      <div className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-indigo-600/5"></div>
        <div className="relative max-w-6xl mx-auto px-6 py-12">
          <div className="text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl mb-6 shadow-lg">
              <Mail className="w-8 h-8 text-white" />
            </div>
            <h1 className="text-4xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent mb-4">
              Email Writing Assistant
            </h1>
            <p className="text-xl text-slate-600 max-w-2xl mx-auto">
              Transform your thoughts into polished, professional emails with AI assistance
            </p>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-6xl mx-auto px-6 pb-12">
        <div className="grid lg:grid-cols-2 gap-8">
          
          {/* Input Section */}
          <div className="space-y-6">
            <div className="bg-white/70 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-white/20">
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                  <MessageSquare className="w-5 h-5 text-blue-600" />
                </div>
                <h2 className="text-2xl font-semibold text-slate-800">Your Thoughts</h2>
              </div>
              
              <textarea
                value={rawThoughts}
                onChange={(e) => setRawThoughts(e.target.value)}
                onKeyDown={handleKeyPress}
                placeholder="Write what you want to communicate... Don't worry about grammar or structure - just get your ideas down."
                className="w-full h-40 p-4 border border-slate-200 rounded-xl resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/50 backdrop-blur-sm text-slate-700 placeholder-slate-400"
              />
              
              <div className="mt-4 text-sm text-slate-500">
                💡 Tip: Press Cmd/Ctrl + Enter to generate your email
              </div>
            </div>

            {/* Tone Selection */}
            <div className="bg-white/70 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-white/20">
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                  <Sparkles className="w-5 h-5 text-indigo-600" />
                </div>
                <h2 className="text-2xl font-semibold text-slate-800">Email Tone</h2>
              </div>
              
              <div className="grid grid-cols-2 gap-3">
                {tones.map((toneOption) => (
                  <button
                    key={toneOption.value}
                    onClick={() => setTone(toneOption.value)}
                    className={`p-4 rounded-xl border-2 transition-all duration-200 text-left ${
                      tone === toneOption.value
                        ? 'border-blue-500 bg-blue-50 shadow-md'
                        : 'border-slate-200 bg-white/50 hover:border-slate-300 hover:bg-white/70'
                    }`}
                  >
                    <div className="font-medium text-slate-800">{toneOption.label}</div>
                    <div className="text-sm text-slate-600 mt-1">{toneOption.description}</div>
                  </button>
                ))}
              </div>
            </div>

            {/* Context Email Section */}
            <div className="bg-white/70 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-white/20">
              <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                    <User className="w-5 h-5 text-slate-600" />
                  </div>
                  <h2 className="text-2xl font-semibold text-slate-800">Context (Optional)</h2>
                </div>
                <button
                  onClick={() => setShowContext(!showContext)}
                  className="text-blue-600 hover:text-blue-700 font-medium transition-colors"
                >
                  {showContext ? 'Hide' : 'Show'}
                </button>
              </div>
              
              {showContext && (
                <>
                  <p className="text-slate-600 mb-4">
                    Paste the email you're responding to for better context
                  </p>
                  <textarea
                    value={contextEmail}
                    onChange={(e) => setContextEmail(e.target.value)}
                    placeholder="Paste the original email here..."
                    className="w-full h-32 p-4 border border-slate-200 rounded-xl resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/50 backdrop-blur-sm text-slate-700 placeholder-slate-400"
                  />
                </>
              )}
            </div>

            {/* Generate Button */}
            <button
              onClick={generateEmail}
              disabled={isLoading || !rawThoughts.trim()}
              className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 px-8 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-3"
            >
              {isLoading ? (
                <>
                  <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  Crafting your email...
                </>
              ) : (
                <>
                  <Send className="w-5 h-5" />
                  Generate Email
                </>
              )}
            </button>
          </div>

          {/* Output Section */}
          <div className="space-y-6">
            <div className="bg-white/70 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-white/20 min-h-96">
              <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <Mail className="w-5 h-5 text-green-600" />
                  </div>
                  <h2 className="text-2xl font-semibold text-slate-800">Generated Email</h2>
                </div>
                
                {generatedEmail && (
                  <button
                    onClick={copyToClipboard}
                    className="flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors text-slate-700 font-medium"
                  >
                    {copied ? (
                      <>
                        <Check className="w-4 h-4 text-green-600" />
                        Copied!
                      </>
                    ) : (
                      <>
                        <Copy className="w-4 h-4" />
                        Copy
                      </>
                    )}
                  </button>
                )}
              </div>
              
              {generatedEmail ? (
                <div className="bg-white/80 rounded-xl p-6 border border-slate-200">
                  <pre className="whitespace-pre-wrap font-sans text-slate-700 leading-relaxed">
                    {generatedEmail}
                  </pre>
                </div>
              ) : (
                <div className="flex flex-col items-center justify-center h-64 text-slate-400">
                  <Mail className="w-16 h-16 mb-4 opacity-50" />
                  <p className="text-lg">Your polished email will appear here</p>
                  <p className="text-sm mt-2">Enter your thoughts and select a tone to get started</p>
                </div>
              )}
            </div>

            {/* Tips */}
            <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
              <h3 className="font-semibold text-slate-800 mb-3">✨ Pro Tips</h3>
              <ul className="text-sm text-slate-600 space-y-2">
                <li>• Be specific about what you want to achieve</li>
                <li>• Include key details even if roughly written</li>
                <li>• Try different tones to see what works best</li>
                <li>• Add context for more personalized responses</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}