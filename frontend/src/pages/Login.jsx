// ============================================
// pages/Login.jsx
// ============================================

import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';
import Button from '../components/common/Button';
import Input from '../components/common/Input';
import Card from '../components/common/Card';
import {SunIcon, MoonIcon,LogInIcon} from 'lucide-react';
import '../styles/Login.css';

const Login = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const { theme, toggleTheme } = useTheme();

  const [formData, setFormData] = useState({
    username: '',
    password: '',
  });

  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
    // Effacer erreur du champ
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: '',
      }));
    }
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.password) {
      newErrors.password = 'Mot de passe requis';
    } else if (formData.password.length < 6) {
      newErrors.password = 'Minimum 6 caractères';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage('');

    if (!validate()) {
      return;
    }

    setLoading(true);

    try {
      const result = await login(formData.username, formData.password);

      if (result.success) {
        navigate('/dashboard');
      } else {
        setMessage(result.message || 'Erreur de connexion');
      }
    } catch (error) {
      setMessage('Erreur serveur. Veuillez réessayer.');
    } finally {
      setLoading(false);
    }
  };


  return (
    <div className="login-page">
      <button className="login-theme-toggle" onClick={toggleTheme} title="Changer thème">
        {theme === 'dark' ? (<MoonIcon/>) : (<SunIcon/>)}
      </button>

      <div className="login-container">
        <Card className="login-card">
          <div className="login-header">
            {/* <div className="login-icon">🛍️</div> */}
            <LogInIcon className="login-icon" />
            <h1 className="login-title">Ma Boutique</h1>
            <p className="login-sSSubtitle">Connexion à votre espace</p>
          </div>

          <form onSubmit={handleSubmit} className="login-form">
            <Input
              label="Username"
              type="text"
              name="username"
              value={formData.usename}
              onChange={handleChange}
              error={errors.username}
              placeholder="christian"
              fullWidth
              autoComplete="username"
            />

            <Input
              label="Mot de passe"
              type="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              error={errors.password}
              placeholder="••••••"
              fullWidth
              autoComplete="current-password"
            />

            {message && (
              <div className="login-message login-message-error">
                {message}
              </div>
            )}

            <Button
              type="submit"
              variant="primary"
              size="lg"
              fullWidth
              loading={loading}
            >
              Se connecter
            </Button>
          </form>
        </Card>

        <div className="login-footer">
          <p>© 2026 Ma Boutique - Application locale</p>
        </div>
      </div>
    </div>
  );
};

export default Login;


