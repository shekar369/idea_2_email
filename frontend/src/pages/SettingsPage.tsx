import React, { useState, useEffect, FormEvent } from 'react';
import { fetchLlmSettings, updateLlmSettings } from '../services/api'; // Import API functions
import { useAuth } from '../contexts/AuthContext';
import { Save, AlertCircle, CheckCircle, Settings as SettingsIcon, Server, KeyRound, LinkIcon } from 'lucide-react';

interface LLMSettings {
  preferred_llm: string;
  ollama_endpoint?: string | null;
  openai_api_key_set?: boolean; // Just to indicate if set, not the key itself
  claude_api_key_set?: boolean;
  gemini_api_key_set?: boolean;
  groq_api_key_set?: boolean;
  cohere_api_key_set?: boolean;
  // Add new input fields for actual keys for submission
  openai_api_key?: string | null;
  claude_api_key?: string | null;
  gemini_api_key?: string | null;
  groq_api_key?: string | null;
  cohere_api_key?: string | null;
}

const llmOptions = [
  { value: 'ollama', label: 'Ollama (Local)' },
  { value: 'openai', label: 'OpenAI' },
  { value: 'claude', label: 'Claude (Anthropic)' },
  { value: 'gemini', label: 'Gemini (Google)' },
  { value: 'groq', label: 'Groq' },
  { value: 'cohere', label: 'Cohere' },
];

const SettingsPage: React.FC = () => {
  const { token } = useAuth(); // For ensuring user is authenticated if needed, though ProtectedRoute handles it
  const [settings, setSettings] = useState<Partial<LLMSettings>>({ preferred_llm: 'ollama' });
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // State for API key inputs - these are separate from 'settings' to avoid displaying fetched keys
  const [ollamaEndpointInput, setOllamaEndpointInput] = useState('');
  const [openaiApiKeyInput, setOpenaiApiKeyInput] = useState('');
  const [claudeApiKeyInput, setClaudeApiKeyInput] = useState('');
  const [geminiApiKeyInput, setGeminiApiKeyInput] = useState('');
  const [groqApiKeyInput, setGroqApiKeyInput] = useState('');
  const [cohereApiKeyInput, setCohereApiKeyInput] = useState('');


  useEffect(() => {
    const fetchSettings = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await fetchLlmSettings();
        if (response.data && response.data.settings) {
          setSettings(response.data.settings);
          setOllamaEndpointInput(response.data.settings.ollama_endpoint || '');
          // API key inputs remain blank by default, only for entering new keys
        } else {
          throw new Error("Failed to fetch settings or received unexpected data format.");
        }
      } catch (err: any) {
        setError(err.response?.data?.error || err.message || 'Failed to fetch settings.');
      } finally {
        setIsLoading(false);
      }
    };

    if (token) { // Ensure there's a token before trying to fetch settings
      fetchSettings();
    }
  }, [token]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    if (name === 'preferred_llm') {
      setSettings(prev => ({ ...prev, preferred_llm: value }));
    } else if (name === 'ollama_endpoint_input') {
      setOllamaEndpointInput(value);
    } else if (name === 'openai_api_key_input') {
      setOpenaiApiKeyInput(value);
    } else if (name === 'claude_api_key_input') {
      setClaudeApiKeyInput(value);
    } else if (name === 'gemini_api_key_input') {
        setGeminiApiKeyInput(value);
    } else if (name === 'groq_api_key_input') {
        setGroqApiKeyInput(value);
    } else if (name === 'cohere_api_key_input') {
        setCohereApiKeyInput(value);
    }
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setError(null);
    setSuccessMessage(null);

    const dataToSave: Partial<LLMSettings> = {
      preferred_llm: settings.preferred_llm,
    };

    if (settings.preferred_llm === 'ollama') {
      dataToSave.ollama_endpoint = ollamaEndpointInput || null;
    }
    // Only include API keys if they have been entered by the user
    if (openaiApiKeyInput) dataToSave.openai_api_key = openaiApiKeyInput;
    if (claudeApiKeyInput) dataToSave.claude_api_key = claudeApiKeyInput;
    if (geminiApiKeyInput) dataToSave.gemini_api_key = geminiApiKeyInput;
    if (groqApiKeyInput) dataToSave.groq_api_key = groqApiKeyInput;
    if (cohereApiKeyInput) dataToSave.cohere_api_key = cohereApiKeyInput;


    try {
      const response = await updateLlmSettings(dataToSave);

      if (response.data && response.data.settings) {
        setSettings(prev => ({...prev, ...response.data.settings})); // Update local state with new indicators
        setSuccessMessage(response.data.message || 'Settings saved successfully!');
         // Clear key inputs after successful save for security
        setOpenaiApiKeyInput('');
        setClaudeApiKeyInput('');
        setGeminiApiKeyInput('');
        setGroqApiKeyInput('');
        setCohereApiKeyInput('');
      } else {
        throw new Error("Failed to save settings or received unexpected data format.");
      }

    } catch (err: any) {
      setError(err.response?.data?.error || err.message || 'Failed to save settings.');
    } finally {
      setIsSaving(false);
      setTimeout(() => { // Clear messages after a few seconds
        setSuccessMessage(null);
        setError(null);
      }, 4000);
    }
  };

  if (isLoading) {
    return <div className="p-6 text-center text-slate-600 dark:text-slate-300">Loading settings...</div>;
  }

  const renderApiKeyInput = (llmKey: keyof LLMSettings, currentInputValue: string, onChangeHandler: (val: string) => void, placeholder: string, isSet?: boolean) => (
    <div>
      <label htmlFor={`${llmKey}_input`} className="block text-sm font-medium text-gray-700 dark:text-slate-200 capitalize">
        {llmKey.replace('_api_key', '').replace('_', ' ')} API Key {isSet && !currentInputValue && <span className="text-green-500">(Already Set)</span>}
      </label>
      <div className="mt-1 relative rounded-md shadow-sm">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <KeyRound className="h-5 w-5 text-gray-400" />
        </div>
        <input
          id={`${llmKey}_input`}
          name={`${llmKey}_input`}
          type="password" // Keep as password for obfuscation
          value={currentInputValue}
          onChange={(e) => onChangeHandler(e.target.value)}
          placeholder={isSet && !currentInputValue ? "Enter new key to update" : placeholder}
          className="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 dark:border-slate-500 rounded-md placeholder-gray-400 dark:placeholder-slate-400 focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm bg-white dark:bg-slate-600 text-gray-900 dark:text-white"
        />
      </div>
       {isSet && !currentInputValue && <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Leave blank to keep existing key.</p>}
    </div>
  );


  return (
    <div className="max-w-2xl mx-auto bg-white dark:bg-slate-800 shadow-xl rounded-lg p-6 sm:p-8">
      <div className="flex items-center mb-6">
        <SettingsIcon className="h-8 w-8 text-sky-500 mr-3" />
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">LLM Settings</h1>
      </div>

      {error && (
        <div className="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
          <div className="flex">
            <AlertCircle className="h-5 w-5 text-red-500 mr-2"/>
            <div>
              <p className="font-bold">Error</p>
              <p>{error}</p>
            </div>
          </div>
        </div>
      )}
      {successMessage && (
        <div className="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
          <div className="flex">
            <CheckCircle className="h-5 w-5 text-green-500 mr-2"/>
            <div>
              <p className="font-bold">Success</p>
              <p>{successMessage}</p>
            </div>
          </div>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label htmlFor="preferred_llm" className="block text-sm font-medium text-gray-700 dark:text-slate-200">
            Preferred LLM Provider
          </label>
          <div className="mt-1 relative rounded-md shadow-sm">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Server className="h-5 w-5 text-gray-400" />
            </div>
            <select
              id="preferred_llm"
              name="preferred_llm"
              value={settings.preferred_llm || 'ollama'}
              onChange={handleInputChange}
              className="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 dark:border-slate-500 rounded-md focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm bg-white dark:bg-slate-600 text-gray-900 dark:text-white"
            >
              {llmOptions.map(option => (
                <option key={option.value} value={option.value}>{option.label}</option>
              ))}
            </select>
          </div>
           <p className="mt-2 text-sm text-gray-500 dark:text-slate-400">
            Currently active: <span className="font-semibold text-sky-600 dark:text-sky-400">{llmOptions.find(o => o.value === settings.preferred_llm)?.label || settings.preferred_llm}</span>
          </p>
        </div>

        {settings.preferred_llm === 'ollama' && (
          <div>
            <label htmlFor="ollama_endpoint_input" className="block text-sm font-medium text-gray-700 dark:text-slate-200">
              Ollama Local Endpoint URL
            </label>
            <div className="mt-1 relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <LinkIcon className="h-5 w-5 text-gray-400" />
                </div>
                <input
                id="ollama_endpoint_input"
                name="ollama_endpoint_input"
                type="url"
                value={ollamaEndpointInput}
                onChange={handleInputChange}
                placeholder="e.g., http://localhost:11434"
                className="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 dark:border-slate-500 rounded-md placeholder-gray-400 dark:placeholder-slate-400 focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm bg-white dark:bg-slate-600 text-gray-900 dark:text-white"
                />
            </div>
          </div>
        )}

        {settings.preferred_llm === 'openai' && renderApiKeyInput('openai_api_key', openaiApiKeyInput, setOpenaiApiKeyInput, "sk-xxxxxxxxxx", settings.openai_api_key_set)}
        {settings.preferred_llm === 'claude' && renderApiKeyInput('claude_api_key', claudeApiKeyInput, setClaudeApiKeyInput, "claude-api-key-xxxx", settings.claude_api_key_set)}
        {settings.preferred_llm === 'gemini' && renderApiKeyInput('gemini_api_key', geminiApiKeyInput, setGeminiApiKeyInput, "gemini-api-key-xxxx", settings.gemini_api_key_set)}
        {settings.preferred_llm === 'groq' && renderApiKeyInput('groq_api_key', groqApiKeyInput, setGroqApiKeyInput, "gsk_xxxx", settings.groq_api_key_set)}
        {settings.preferred_llm === 'cohere' && renderApiKeyInput('cohere_api_key', cohereApiKeyInput, setCohereApiKeyInput, "cohere-api-key-xxxx", settings.cohere_api_key_set)}

        <div className="pt-2">
          <button
            type="submit"
            disabled={isSaving}
            className="w-full flex items-center justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <Save className="w-5 h-5 mr-2" />
            {isSaving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </form>
       <p className="mt-6 text-xs text-slate-500 dark:text-slate-400 text-center">
        API keys are stored securely and are only sent when making requests to the respective LLM providers. They are not displayed here after saving.
      </p>
    </div>
  );
};

export default SettingsPage;
