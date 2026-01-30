// ============================================
// pages/Login.jsx - Version Split Screen
// ============================================

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';
import { Eye, EyeOff, Sparkles } from 'lucide-react';
import '../styles/Login.css';

// 🏢 CONFIGURATION ENTREPRISE
// Pour l'image, mettez votre logo dans public/logo.png
// Ensuite utilisez: logo: '/logo.png'
const COMPANY_CONFIG = {
  name: 'Express Sale',
  logo: '/logo.jpg', // Exemple: '/logo.png' ou import logo from '../assets/logo.png'
  creator: 'Manidina++'
};

// 💬 CITATIONS INSPIRANTES
const QUOTES = [
  {
    text: "La simplicité est la sophistication suprême.",
    author: "Léonard de Vinci",
    color: "#0071e3"
  },
  {
    text: "Le design n'est pas seulement ce à quoi ça ressemble. Le design, c'est comment ça fonctionne.",
    author: "Steve Jobs",
    color: "#30d158"
  },
  {
    text: "L'innovation distingue un leader d'un suiveur.",
    author: "Steve Jobs",
    color: "#ff9f0a"
  },
  {
    text: "La perfection est atteinte, non pas lorsqu'il n'y a plus rien à ajouter, mais lorsqu'il n'y a plus rien à retirer.",
    author: "Antoine de Saint-Exupéry",
    color: "#0a84ff"
  },
  {
    text: "Le détail fait la perfection, et la perfection n'est pas un détail.",
    author: "Léonard de Vinci",
    color: "#ff3b30"
  },
  {
    text: "La qualité vaut mieux que la quantité.",
    author: "Steve Jobs",
    color: "#30d158"
  },
  {
    text: "Soyez vous-même, tous les autres sont déjà pris.",
    author: "Oscar Wilde",
    color: "#ff9f0a"
  },
  {
    text: "Le succès n'est pas final, l'échec n'est pas fatal : c'est le courage de continuer qui compte.",
    author: "Winston Churchill",
    color: "#0071e3"
  },
  {
    text: "La créativité, c'est l'intelligence qui s'amuse.",
    author: "Albert Einstein",
    color: "#ff3b30"
  },
  {
    text: "Le meilleur moment pour planter un arbre était il y a 20 ans. Le deuxième meilleur moment est maintenant.",
    author: "Proverbe chinois",
    color: "#30d158"
  },
  {
    text: "Votre temps est limité, ne le gaspillez pas en vivant la vie de quelqu'un d'autre.",
    author: "Steve Jobs",
    color: "#0a84ff"
  },
  {
    text: "L'excellence n'est pas une destination, c'est un voyage continu.",
    author: "Brian Tracy",
    color: "#ff9f0a"
  },
  {
    text: "Chaque expert était autrefois un débutant.",
    author: "Robin Sharma",
    color: "#0071e3"
  },
  {
    text: "La seule façon de faire du bon travail est d'aimer ce que vous faites.",
    author: "Steve Jobs",
    color: "#30d158"
  },
  {
    text: "Ce qui compte ne peut pas toujours être compté, et ce qui peut être compté ne compte pas forcément.",
    author: "Albert Einstein",
    color: "#ff3b30"
  },
  {
    text: "Le génie, c'est 1% d'inspiration et 99% de transpiration.",
    author: "Thomas Edison",
    color: "#0a84ff"
  },
  {
    text: "La vie est 10% ce qui vous arrive et 90% comment vous y réagissez.",
    author: "Charles Swindoll",
    color: "#ff9f0a"
  },
  {
    text: "Osez être grand. L'audace possède du génie, du pouvoir et de la magie.",
    author: "Goethe",
    color: "#0071e3"
  },
  {
    text: "La persévérance est la clé de la réussite.",
    author: "Proverbe",
    color: "#30d158"
  },
  {
    text: "Crois en tes rêves et ils se réaliseront peut-être. Crois en toi et ils se réaliseront sûrement.",
    author: "Martin Luther King Jr.",
    color: "#ff3b30"
  }
];

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
  const [showPassword, setShowPassword] = useState(false);
  const [currentQuoteIndex, setCurrentQuoteIndex] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);

  // Animation des citations - Change toutes les 6 secondes
  useEffect(() => {
    const timer = setInterval(() => {
      setIsAnimating(true);
      
      setTimeout(() => {
        setCurrentQuoteIndex((prev) => (prev + 1) % QUOTES.length);
        setIsAnimating(false);
      }, 600);
    }, 6000);

    return () => clearInterval(timer);
  }, []);

  const currentQuote = QUOTES[currentQuoteIndex];

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: '',
      }));
    }
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.username) {
      newErrors.username = 'Nom d\'utilisateur requis';
    }

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
    <div className="login-page-split">
      {/* Toggle thème */}
      <button 
        className="theme-toggle-split" 
        onClick={toggleTheme}
        aria-label="Changer de thème"
        type="button"
      >
        <div className={`toggle-track ${theme === 'dark' ? 'dark' : ''}`}>
          <div className="toggle-thumb">
            {theme === 'dark' ? '🌙' : '☀️'}
          </div>
        </div>
      </button>

      {/* === CÔTÉ GAUCHE : CITATIONS === */}
      <div className="login-quotes-side" style={{ '--quote-color': currentQuote.color }}>
        <div className="quotes-background">
          <div className="floating-orb orb-1"></div>
          <div className="floating-orb orb-2"></div>
          <div className="floating-orb orb-3"></div>
        </div>

        <div className="quotes-content">
          <div className="quote-icon">
            <Sparkles size={48} strokeWidth={1.5} />
          </div>

          <div className={`quote-box ${isAnimating ? 'fade-out' : 'fade-in'}`}>
            <p className="quote-text-large">"{currentQuote.text}"</p>
            <p className="quote-author-large">— {currentQuote.author}</p>
          </div>

          <div className="quote-progress">
            {QUOTES.map((_, index) => (
              <div
                key={index}
                className={`progress-dot ${index === currentQuoteIndex ? 'active' : ''}`}
              />
            ))}
          </div>
        </div>
      </div>

      {/* === CÔTÉ DROIT : FORMULAIRE === */}
      <div className="login-form-side">
        <div className="form-container">
          
          {/* Logo et titre */}
          <div className="form-header">
            {COMPANY_CONFIG.logo ? (
              <img 
                src={COMPANY_CONFIG.logo} 
                alt={COMPANY_CONFIG.name} 
                className="company-logo-split"
              />
            ) : (
              <div className="company-logo-placeholder-split">
                <svg viewBox="0 0 24 24" fill="none">
                  <path 
                    d="M12 2L2 7V17L12 22L22 17V7L12 2Z" 
                    stroke="currentColor" 
                    strokeWidth="2" 
                    strokeLinecap="round" 
                    strokeLinejoin="round"
                  />
                  <path 
                    d="M12 22V12" 
                    stroke="currentColor" 
                    strokeWidth="2" 
                    strokeLinecap="round" 
                    strokeLinejoin="round"
                  />
                  <path 
                    d="M2 7L12 12L22 7" 
                    stroke="currentColor" 
                    strokeWidth="2" 
                    strokeLinecap="round" 
                    strokeLinejoin="round"
                  />
                </svg>
              </div>
            )}
            <h1 className="form-title">{COMPANY_CONFIG.name}</h1>
            <p className="form-subtitle">Bienvenue ! Connectez-vous pour continuer</p>
          </div>

          {/* Formulaire */}
          <form onSubmit={handleSubmit} className="login-form-split" noValidate>
            
            {/* Username */}
            <div className="form-group-split">
              <label htmlFor="username" className="form-label-split">
                Nom d'utilisateur
              </label>
              <input
                id="username"
                type="text"
                name="username"
                value={formData.username}
                onChange={handleChange}
                placeholder="Entrez votre nom d'utilisateur"
                className={`form-input-split ${errors.username ? 'error' : ''}`}
                autoComplete="username"
                disabled={loading}
                autoFocus
              />
              {errors.username && (
                <span className="form-error-split">{errors.username}</span>
              )}
            </div>

            {/* Password */}
            <div className="form-group-split">
              <label htmlFor="password" className="form-label-split">
                Mot de passe
              </label>
              <div className="password-wrapper-split">
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  placeholder="Entrez votre mot de passe"
                  className={`form-input-split ${errors.password ? 'error' : ''}`}
                  autoComplete="current-password"
                  disabled={loading}
                />
                <button
                  type="button"
                  className="password-toggle-split"
                  onClick={() => setShowPassword(!showPassword)}
                  aria-label={showPassword ? 'Masquer' : 'Afficher'}
                  disabled={loading}
                  tabIndex={-1}
                >
                  {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                </button>
              </div>
              {errors.password && (
                <span className="form-error-split">{errors.password}</span>
              )}
            </div>

            {/* Message d'erreur */}
            {message && (
              <div className="alert-error-split" role="alert">
                {message}
              </div>
            )}

            {/* Bouton */}
            <button
              type="submit"
              className="login-button-split"
              disabled={loading}
            >
              {loading ? (
                <div className="loading-spinner-split">
                  <div className="spinner-split"></div>
                  <span>Connexion en cours...</span>
                </div>
              ) : (
                'Se connecter'
              )}
            </button>
          </form>

          {/* Footer */}
          <div className="form-footer">
            <p>© 2026 {COMPANY_CONFIG.name} · By {COMPANY_CONFIG.creator} of HERIMANANTSOA Christian</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;