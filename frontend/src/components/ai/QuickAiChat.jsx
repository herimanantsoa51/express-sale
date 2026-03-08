import React, { useEffect, useMemo, useState } from 'react';
import { X, Send, Loader2, MessageSquare, CheckCircle2, XCircle, RotateCcw } from 'lucide-react';
import { AnimatePresence, motion } from 'framer-motion';
import { useNavigate } from 'react-router-dom';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeSanitize from 'rehype-sanitize';
import aiTaskService from '../../services/aiTaskService';
import '../../styles/QuickAiChat.css';

const ROUTE_KEYWORDS = [
  { key: 'produits', path: '/produits' },
  { key: 'fournisseurs', path: '/fournisseurs' },
  { key: 'transitaires', path: '/transitaires' },
  { key: 'clients', path: '/clients' },
  { key: 'ventes', path: '/ventes' },
  { key: 'statistiques', path: '/statistiques' },
  { key: 'parametres', path: '/parametres' },
  { key: 'ai providers', path: '/parametres/ai-providers' },
  { key: 'ai tasks', path: '/parametres/ai-tasks' },
];

const inferRedirect = (text) => {
  const lower = (text || '').toLowerCase();
  if (!lower.includes('redirige') && !lower.includes('redirection')) return null;
  const hit = ROUTE_KEYWORDS.find((r) => lower.includes(r.key));
  return hit ? hit.path : null;
};

const QuickAiChat = ({ open, onClose }) => {
  const navigate = useNavigate();
  const [input, setInput] = useState('');
  const [messages, setMessages] = useState([]);
  const [lastStatus, setLastStatus] = useState(null);
  const [isRunning, setIsRunning] = useState(false);
  const [ragStatus, setRagStatus] = useState(null);
  const [ragLoading, setRagLoading] = useState(false);
  const [manifest, setManifest] = useState(null);

  const canSend = input.trim().length > 0 && !isRunning;

  const getSessionId = () => {
    const key = 'langgraph_session_id';
    let sid = localStorage.getItem(key);
    if (!sid) {
      sid = `lg_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
      localStorage.setItem(key, sid);
    }
    return sid;
  };

  const handleSend = async () => {
    if (!canSend) return;
    const content = input.trim();
    setInput('');
    setMessages((prev) => [...prev, { role: 'user', text: content }]);

    try {
      setIsRunning(true);
      const sessionId = getSessionId();
      const result = await aiTaskService.langgraphQuery({
        text: content,
        session_id: sessionId,
        debug: true,
      });

      const reply = result?.response || result?.message || 'Aucune réponse AI.';
      setLastStatus({
        status: result?.source ? 'completed' : 'failed',
        message: result?.message || null,
      });

      const fallbackPath = inferRedirect(`${content} ${reply}`);
      if (fallbackPath) {
        navigate(fallbackPath);
      }

      setMessages((prev) => [...prev, { role: 'assistant', text: reply }]);
    } catch (error) {
      setLastStatus({ status: 'failed', message: 'Erreur lors de la requête AI.' });
      setMessages((prev) => [...prev, { role: 'assistant', text: 'Erreur lors de la requête AI.' }]);
    } finally {
      setIsRunning(false);
    }
  };

  const Markdown = ({ text }) => (
    <ReactMarkdown
      remarkPlugins={[remarkGfm]}
      rehypePlugins={[rehypeSanitize]}
      components={{
        a: ({ href, children, ...props }) => {
          const isInternal = typeof href === 'string' && href.startsWith('/');
          const allowedPatterns = (manifest?.pages || []).map((p) => p.path).filter(Boolean);
          const matchPattern = (path, pattern) => {
            const re = new RegExp(`^${pattern.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&').replace(/\\\\:([A-Za-z0-9_]+)/g, '[^/]+')}$`);
            return re.test(path);
          };
          const isAllowedInternal = (path) => allowedPatterns.some((p) => matchPattern(path, p));
          if (isInternal) {
            if (allowedPatterns.length > 0 && !isAllowedInternal(href)) {
              return <span {...props} className="quick-ai-link-disabled">{children}</span>;
            }
            return (
              <a
                {...props}
                href={href}
                onClick={(e) => {
                  e.preventDefault();
                  navigate(href);
                  onClose?.();
                }}
              >
                {children}
              </a>
            );
          }
          return (
            <a {...props} href={href} target="_blank" rel="noopener noreferrer">
              {children}
            </a>
          );
        },
      }}
    >
      {text}
    </ReactMarkdown>
  );

  const renderedMessages = useMemo(() => {
    return messages.length > 0 ? messages : [
      { role: 'assistant', text: 'Bonjour ! Posez une question rapide pour tester l’AI.' },
    ];
  }, [messages]);

  useEffect(() => {
    if (!open) return;
    const loadStatus = async () => {
      try {
        const status = await aiTaskService.ragStatus();
        setRagStatus(status);
        const man = await aiTaskService.ragManifest();
        setManifest(man);
      } catch (error) {
        setRagStatus(null);
        setManifest(null);
      }
    };
    loadStatus();
  }, [open]);

  const handleRefreshRag = async () => {
    try {
      setRagLoading(true);
      await aiTaskService.ragRefresh();
      const status = await aiTaskService.ragStatus();
      setRagStatus(status);
      const man = await aiTaskService.ragManifest();
      setManifest(man);
    } finally {
      setRagLoading(false);
    }
  };

  return (
    <AnimatePresence>
      {open && (
        <>
          <motion.div
            className="quick-ai-modal-backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
          />
          <motion.div
            className="quick-ai-modal"
            initial={{ opacity: 0, scale: 0.95, y: -20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: -20 }}
            transition={{ type: 'spring', damping: 25, stiffness: 300 }}
          >
            <div className="quick-ai-modal-header">
              <h3 className="quick-ai-modal-title">
                <MessageSquare size={18} />
                Chat AI rapide
              </h3>
              <div className="quick-ai-header-actions">
                {ragStatus?.last_refresh ? (
                  <span className="quick-ai-rag-badge">RAG OK</span>
                ) : (
                  <span className="quick-ai-rag-badge quick-ai-rag-badge--warn">RAG ?</span>
                )}
                <button
                  className="quick-ai-action-btn"
                  onClick={handleRefreshRag}
                  disabled={ragLoading}
                  title="Rafraîchir le RAG"
                >
                  {ragLoading ? <Loader2 size={16} className="quick-ai-spinner" /> : <RotateCcw size={16} />}
                </button>
                <button className="quick-ai-close-btn" onClick={onClose} title="Fermer">
                  <X size={18} />
                </button>
              </div>
            </div>

            <div className="quick-ai-modal-body">
              {lastStatus?.status && (
                <div className={`quick-ai-status ${lastStatus.status === 'failed' ? 'quick-ai-status--fail' : 'quick-ai-status--ok'}`}>
                  {lastStatus.status === 'failed' ? <XCircle size={14} /> : <CheckCircle2 size={14} />}
                  <span>{lastStatus.status === 'failed' ? (lastStatus.message || 'Échec') : 'OK'}</span>
                </div>
              )}

              <div className="quick-ai-list">
                {renderedMessages.map((msg, idx) => (
                  <div key={idx} className={`quick-ai-item quick-ai-item--${msg.role}`}>
                    <div className="quick-ai-item-content">
                      {msg.role === 'assistant' ? <Markdown text={msg.text} /> : msg.text}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="quick-ai-modal-footer">
              <input
                className="quick-ai-input"
                type="text"
                placeholder="Écrivez votre message..."
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    handleSend();
                  }
                }}
              />
              <button className="quick-ai-send" onClick={handleSend} disabled={!canSend}>
                {isRunning ? <Loader2 size={16} className="quick-ai-spinner" /> : <Send size={16} />}
              </button>
            </div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
};

export default QuickAiChat;
