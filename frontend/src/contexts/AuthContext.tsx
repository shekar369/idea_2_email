import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';

// Define the shape of the user object and auth context
interface User {
  id: number;
  email: string;
  // Add other user properties as needed, e.g., name, sso_provider
}

interface AuthContextType {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean; // To handle initial loading of token/user
  login: (newToken: string, userData: User) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [token, setToken] = useState<string | null>(localStorage.getItem('authToken'));
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true); // Start with loading true

  useEffect(() => {
    const storedToken = localStorage.getItem('authToken');
    const storedUser = localStorage.getItem('authUser');

    if (storedToken) {
      setToken(storedToken);
      if (storedUser) {
        try {
          setUser(JSON.parse(storedUser));
        } catch (error) {
          console.error("Failed to parse stored user:", error);
          localStorage.removeItem('authUser'); // Clear corrupted user data
        }
      }
    }
    setIsLoading(false); // Finished loading from localStorage
  }, []);

  // Sets the token and user data in state and localStorage.
  const login = (newToken: string, userData: User) => {
    localStorage.setItem('authToken', newToken);
    localStorage.setItem('authUser', JSON.stringify(userData));
    setToken(newToken);
    setUser(userData);
  };

  // Clears token and user data from state and localStorage.
  // Optionally, could also call a backend API endpoint to invalidate the token on the server-side if implemented.
  const logout = () => {
    localStorage.removeItem('authToken');
    localStorage.removeItem('authUser');
    setToken(null);
    setUser(null);
    // Example: apiClient.post('/auth/logout').catch(err => console.error("Logout API call failed", err));
  };

  return (
    <AuthContext.Provider value={{ token, user, isAuthenticated: !!token, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
