// ============================================
// components/NetworkInfo.jsx - Version simplifiée
// ============================================

import { useState, useEffect, useRef } from 'react';
import { Network, QrCode, Download, Copy, Check } from 'lucide-react';
import QRCodeLib from 'qrcode';
import { toast } from 'react-toastify';
import '../styles/NetworkInfo.css';

const NetworkInfo = () => {
  const [frontendUrl, setFrontendUrl] = useState('');
  const [apiUrl, setApiUrl] = useState('');
  const [currentIp, setCurrentIp] = useState('');
  const [copied, setCopied] = useState(false);
  const qrCanvasRef = useRef(null);

  useEffect(() => {
    detectUrls();
  }, []);

  useEffect(() => {
    if (frontendUrl) {
      generateQRCode(frontendUrl);
    }
  }, [frontendUrl]);

  const detectUrls = () => {
    const hostname = window.location.hostname;
    const port = window.location.port || '3000';
    const apiPort = '8000';

    // Construire les URLs
    const fUrl = `http://${hostname}:${port}`;
    const aUrl = `http://${hostname}:${apiPort}/api`;

    setFrontendUrl(fUrl);
    setApiUrl(aUrl);
    setCurrentIp(hostname);
  };

  const generateQRCode = async (url) => {
    if (!qrCanvasRef.current) return;

    try {
      await QRCodeLib.toCanvas(qrCanvasRef.current, url, {
        width: 200,
        margin: 2,
        color: {
          dark: '#000000',
          light: '#ffffff',
        },
      });
    } catch (error) {
      console.error('Erreur génération QR code:', error);
    }
  };

  const handleCopyUrl = async () => {
    try {
      await navigator.clipboard.writeText(frontendUrl);
      setCopied(true);
      toast.success('URL copiée !');
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      toast.error('Erreur lors de la copie');
    }
  };

  const handleDownloadQR = () => {
    if (!qrCanvasRef.current) return;

    try {
      const url = qrCanvasRef.current.toDataURL('image/png');
      const link = document.createElement('a');
      link.download = `qrcode-app-${currentIp}.png`;
      link.href = url;
      link.click();
      toast.success('QR Code téléchargé');
    } catch (error) {
      toast.error('Erreur lors du téléchargement');
    }
  };

  return (
    <div className="network-info">
      <div className="network-info__header">
        <div className="network-info__icon">
          <Network size={20} />
        </div>
        <div>
          <h3 className="network-info__title">Accès Réseau Local</h3>
          <p className="network-info__subtitle">
            Scannez ce QR code pour accéder depuis un autre appareil sur le même réseau
          </p>
        </div>
      </div>

      <div className="network-info__content">
        {/* QR Code */}
        <div className="network-info__qr-section">
          <div className="network-info__qr-container">
            <canvas ref={qrCanvasRef} />
          </div>
          <button 
            onClick={handleDownloadQR} 
            className="network-info__download-btn"
          >
            <Download size={16} />
            <span>Télécharger</span>
          </button>
        </div>

        {/* URLs */}
        <div className="network-info__urls">
          <div className="network-info__url-item">
            <label className="network-info__url-label">URL Application</label>
            <div className="network-info__url-box">
              <code className="network-info__url-text">{frontendUrl}</code>
              <button 
                onClick={handleCopyUrl} 
                className="network-info__copy-btn"
                title="Copier l'URL"
              >
                {copied ? <Check size={16} /> : <Copy size={16} />}
              </button>
            </div>
          </div>

          <div className="network-info__url-item">
            <label className="network-info__url-label">URL API</label>
            <div className="network-info__url-box">
              <code className="network-info__url-text">{apiUrl}</code>
            </div>
          </div>

          <div className="network-info__info-box">
            <p>
              <strong>IP détectée :</strong> {currentIp}
            </p>
            <p className="network-info__help-text">
              L'adresse IP est détectée automatiquement. Si l'IP change, rechargez simplement la page de configuration.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default NetworkInfo;