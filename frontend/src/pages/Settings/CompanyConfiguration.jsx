import React, { useState, useEffect, useRef } from 'react';
import { Building2, Mail, Phone, MapPin, Upload, X, Users, Save, Loader2, Printer, Settings, FileText } from 'lucide-react';
import { toast, ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import '../../styles/CompanyConfiguration.css';
import companyInfoService from '../../services/companyInfoService';

const CompanyConfiguration = () => {
  const [companyData, setCompanyData] = useState({
    name: '',
    phone: '',
    address: '',
    email: '',
    invoice_signature: '',
    logo_path: null,
    printer_path: '/dev/usb/lp0',
    auto_print_immediate_sale: false,
    auto_print_credit: false,
    auto_print_credit_payment: false,
    auto_print_reservation: false,
    auto_print_reservation_complete: false,
    auto_print_cash_count: false,
  });
  const [logoPreview, setLogoPreview] = useState(null);
  const [selectedLogoFile, setSelectedLogoFile] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const fileInputRef = useRef(null);

  useEffect(() => {
    loadCompanyInfo();
  }, []);

  const loadCompanyInfo = async () => {
    try {
      setIsLoading(true);
      const data = await companyInfoService.index();
      setCompanyData(data);
      if (data.logo_path) {
        setLogoPreview(data.logo_path);
      }
    } catch (error) {
      toast.error('Erreur lors du chargement des informations');
    } finally {
      setIsLoading(false);
    }
  };

  const handleInputChange = (field, value) => {
    setCompanyData(prev => ({ ...prev, [field]: value }));
  };

  const handleLogoUpload = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      toast.error('Veuillez sélectionner une image valide');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      toast.error("L'image ne doit pas dépasser 5 MB");
      return;
    }

    const localPreviewUrl = URL.createObjectURL(file);
    setLogoPreview(localPreviewUrl);
    setSelectedLogoFile(file);
    
    toast.info('Logo sélectionné. Enregistrez pour confirmer.');
  };

  const handleRemoveLogo = () => {
    if (logoPreview && logoPreview.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreview);
    }
    
    setLogoPreview(null);
    setSelectedLogoFile(null);
    
    if (companyData.logo_path) {
      setCompanyData(prev => ({ ...prev, logo_path: null }));
    }
    
    toast.info('Logo retiré. Enregistrez pour confirmer.');
  };

  const handleSave = async () => {
    if (!companyData.name) {
      toast.warning('Veuillez remplir les champs obligatoires');
      return;
    }

    try {
      setIsSaving(true);
      
      let updatedData = { ...companyData };
      
       // Si un nouveau logo a été sélectionné, l'uploader d'abord
       if (selectedLogoFile) {
             const compressedFile = await fileService.compressImage(selectedLogoFile);
             const response = await fileService.uploadImage(compressedFile);
             updatedData.logo_path = response.url;
      }


        // Si le logo a été supprimé, supprimer l'ancien du serveur
        if (companyData.logo_path === null && !selectedLogoFile) {
          const originalData = await companyInfoService.index();
          if (originalData.logo_path) {
            await fileService.deleteImage(originalData.logo_path);
          }
        }
              
       // Sauvegarder les informations
      await companyInfoService.update(updatedData);
      setCompanyData(updatedData);
      setSelectedLogoFile(null);
          
      toast.success('Informations sauvegardées avec succès');
    } catch (error) {
      toast.error('Erreur lors de la sauvegarde');
      console.log(error)
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className="company-config__loading-container">
        <Loader2 size={32} className="company-config__spinner" />
      </div>
    );
  }

  return (
    <div className="company-config">
      <ToastContainer position="top-right" autoClose={3000} />
      
      <div className="company-config__content">
        {/* Header */}
        <div className="company-config__header">
          <div>
            <h1 className="company-config__title">Configuration</h1>
            <p className="company-config__subtitle">Gérez les informations de votre entreprise et imprimante</p>
          </div>
          <button onClick={() => window.location.href = '/utilisateurs'} className="company-config__users-btn">
            <Users size={18} />
            <span>Utilisateurs</span>
          </button>
        </div>

        {/* Main Card */}
        <div className="company-config__card">
          {/* Logo Section */}
          <div className="company-config__section">
            <h2 className="company-config__section-title">Logo de l'entreprise</h2>
            <div className="company-config__logo-container">
              <div className="company-config__logo-box">
                {logoPreview ? (
                  <div className="company-config__logo-preview">
                    <img src={logoPreview} alt="Logo" className="company-config__logo-image" />
                    <button onClick={handleRemoveLogo} className="company-config__remove-logo-btn">
                      <X size={16} />
                    </button>
                  </div>
                ) : (
                  <div className="company-config__logo-placeholder">
                    <Building2 size={40} className="company-config__placeholder-icon" />
                  </div>
                )}
              </div>
              <div className="company-config__logo-actions">
                <input ref={fileInputRef} type="file" accept="image/*" onChange={handleLogoUpload} className="company-config__file-input" />
                <button onClick={() => fileInputRef.current?.click()} className="company-config__upload-btn">
                  <Upload size={16} />
                  <span>{logoPreview ? 'Changer le logo' : 'Ajouter un logo'}</span>
                </button>
                <p className="company-config__helper-text">PNG, JPG jusqu'à 5MB</p>
              </div>
            </div>
          </div>

          <div className="company-config__divider" />

          {/* Company Info */}
          <div className="company-config__section">
            <h2 className="company-config__section-title">Informations générales</h2>
            <div className="company-config__form-grid">
              <InputField
                label="Nom de l'entreprise"
                required
                icon={<Building2 size={18} />}
                value={companyData.name}
                onChange={(e) => handleInputChange('name', e.target.value)}
                placeholder="Entrez le nom"
              />
              <InputField
                label="Facebook"
                required
                icon={<Mail size={18} />}
                type="text"
                value={companyData.email}
                onChange={(e) => handleInputChange('email', e.target.value)}
                placeholder="Express Sale"
              />
              <InputField
                label="Téléphone"
                icon={<Phone size={18} />}
                type="tel"
                value={companyData.phone}
                onChange={(e) => handleInputChange('phone', e.target.value)}
                placeholder="+261 XX XX XXX XX"
              />
              <InputField
                label="Adresse"
                icon={<MapPin size={18} />}
                value={companyData.address}
                onChange={(e) => handleInputChange('address', e.target.value)}
                placeholder="Adresse complète"
              />
              <div className="company-config__input-group--full">
                <InputField
                  label="Signature sur les factures"
                  icon={<FileText size={18} />}
                  value={companyData.invoice_signature}
                  onChange={(e) => handleInputChange('invoice_signature', e.target.value)}
                  placeholder="Signature ou phrase courte"
                />
              </div>
            </div>
          </div>

          <div className="company-config__divider" />

          {/* Printer Settings */}
          <div className="company-config__section">
            <div className="company-config__section-header">
              <Printer size={20} />
              <h2 className="company-config__section-title">Paramètres d'impression</h2>
            </div>
            
            <div className="company-config__printer-path">
              <InputField
                label="Chemin de l'imprimante"
                icon={<Settings size={18} />}
                value={companyData.printer_path}
                onChange={(e) => handleInputChange('printer_path', e.target.value)}
                placeholder="/dev/usb/lp0"
              />
              <p className="company-config__printer-help">
                Chemin du périphérique de l'imprimante thermique (ex: /dev/usb/lp0, /dev/usb/lp1)
              </p>
            </div>

            <div className="company-config__auto-print-section">
              <h3 className="company-config__auto-print-title">Impression automatique</h3>
              <div className="company-config__checkbox-grid">
                <CheckboxField
                  label="Vente immédiate"
                  checked={companyData.auto_print_immediate_sale}
                  onChange={(checked) => handleInputChange('auto_print_immediate_sale', checked)}
                />
                <CheckboxField
                  label="Création de crédit"
                  checked={companyData.auto_print_credit}
                  onChange={(checked) => handleInputChange('auto_print_credit', checked)}
                />
                <CheckboxField
                  label="Paiement d'échéance crédit"
                  checked={companyData.auto_print_credit_payment}
                  onChange={(checked) => handleInputChange('auto_print_credit_payment', checked)}
                />
                <CheckboxField
                  label="Création de réservation"
                  checked={companyData.auto_print_reservation}
                  onChange={(checked) => handleInputChange('auto_print_reservation', checked)}
                />
                <CheckboxField
                  label="Finalisation de réservation"
                  checked={companyData.auto_print_reservation_complete}
                  onChange={(checked) => handleInputChange('auto_print_reservation_complete', checked)}
                />
                <CheckboxField
                  label="Comptage de caisse"
                  checked={companyData.auto_print_cash_count}
                  onChange={(checked) => handleInputChange('auto_print_cash_count', checked)}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Save Button */}
        <div className="company-config__footer">
          <button onClick={handleSave} disabled={isSaving} className="company-config__save-btn">
            {isSaving ? <Loader2 size={18} className="company-config__spinner" /> : <Save size={18} />}
            <span>{isSaving ? 'Enregistrement...' : 'Enregistrer les modifications'}</span>
          </button>
        </div>
      </div>
    </div>
  );
};

const InputField = ({ label, required, icon, ...inputProps }) => (
  <div className="company-config__input-group">
    <label className="company-config__label">
      {label} {required && <span className="company-config__required">*</span>}
    </label>
    <div className="company-config__input-wrapper">
      <div className="company-config__input-icon">{icon}</div>
      <input {...inputProps} className="company-config__input" />
    </div>
  </div>
);

const CheckboxField = ({ label, checked, onChange }) => (
  <label className="company-config__checkbox-field">
    <input
      type="checkbox"
      checked={checked}
      onChange={(e) => onChange(e.target.checked)}
      className="company-config__checkbox"
    />
    <span className="company-config__checkbox-label">{label}</span>
  </label>
);

export default CompanyConfiguration;