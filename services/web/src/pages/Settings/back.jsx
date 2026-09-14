import React, { useState, useEffect, useRef } from 'react';
import { Building2, Mail, Phone, MapPin, Upload, X, Users, Save, Loader2,Signature } from 'lucide-react';
import { toast, ToastContainer } from 'react-toastify';
import '../../styles/CompanyConfiguration.css';
import companyInfoService from '../../services/companyInfoService';
import {fileService} from '../../services/fileService';


const CompanyConfiguration = () => {
    const [companyData, setCompanyData] = useState({
      name: '',
      phone: '',
      address: '',
      email: '',
      invoice_signature: '',
      logo_path: null
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
        toast.error('L\'image ne doit pas dépasser 5 MB');
        return;
      }
  
      // Créer une URL locale pour la prévisualisation
      const localPreviewUrl = URL.createObjectURL(file);
      setLogoPreview(localPreviewUrl);
      setSelectedLogoFile(file);
      
      toast.info('Logo sélectionné. Enregistrez pour confirmer.');
    };
  
    const handleRemoveLogo = () => {
      // Révoquer l'URL locale si elle existe
      if (logoPreview && logoPreview.startsWith('blob:')) {
        URL.revokeObjectURL(logoPreview);
      }
      
      setLogoPreview(null);
      setSelectedLogoFile(null);
      
      // Marquer pour suppression si un logo existait déjà
      if (companyData.logo_path) {
        setCompanyData(prev => ({ ...prev, logo_path: null }));
      }
      
      toast.info('Logo retiré. Enregistrez pour confirmer.');
    };
  
    const handleSave = async () => {
      if (!companyData.name || !companyData.email) {
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
        
        // Réinitialiser l'état
        setCompanyData(updatedData);
        setSelectedLogoFile(null);
        
        // Révoquer l'URL blob si elle existe et charger l'URL serveur
        if (logoPreview && logoPreview.startsWith('blob:')) {
          URL.revokeObjectURL(logoPreview);
        }
        if (updatedData.logo_path) {
          setLogoPreview(updatedData.logo_path);
        }
        
        toast.success('Informations sauvegardées avec succès');
      } catch (error) {
        toast.error('Erreur lors de la sauvegarde');
      } finally {
        setIsSaving(false);
      }
    };
  
    const navigateToUsers = () => {
      window.location.href = '/utilisateurs';
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
        <ToastContainer
          position="top-right"
          autoClose={3000}
          hideProgressBar={false}
          newestOnTop={true}
          closeOnClick
          pauseOnHover
          theme="light"
        />
        
        <div className="company-config__content">
          {/* Header */}
          <div className="company-config__header">
            <div>
              <h1 className="company-config__title">Configuration</h1>
              <p className="company-config__subtitle">Gérez les informations de votre entreprise</p>
            </div>
            <button onClick={navigateToUsers} className="company-config__users-btn">
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
                      <button 
                        onClick={handleRemoveLogo} 
                        className="company-config__remove-logo-btn"
                      >
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
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    onChange={handleLogoUpload}
                    className="company-config__file-input"
                  />
                  <button 
                    onClick={() => fileInputRef.current?.click()}
                    className="company-config__upload-btn"
                  >
                    <Upload size={16} />
                    <span>{logoPreview ? 'Changer le logo' : 'Ajouter un logo'}</span>
                  </button>
                  <p className="company-config__helper-text">PNG, JPG jusqu'à 5MB</p>
                </div>
              </div>
            </div>
  
            <div className="company-config__divider" />
  
            {/* Company Info Section */}
            <div className="company-config__section">
              <h2 className="company-config__section-title">Informations générales</h2>
              <div className="company-config__form-grid">
                <div className="company-config__input-group">
                  <label className="company-config__label">
                    Nom de l'entreprise <span className="company-config__required">*</span>
                  </label>
                  <div className="company-config__input-wrapper">
                    <Building2 size={18} className="company-config__input-icon" />
                    <input
                      type="text"
                      value={companyData.name}
                      onChange={(e) => handleInputChange('name', e.target.value)}
                      className="company-config__input"
                      placeholder="Entrez le nom"
                    />
                  </div>
                </div>
  
                <div className="company-config__input-group">
                  <label className="company-config__label">
                    Email <span className="company-config__required">*</span>
                  </label>
                  <div className="company-config__input-wrapper">
                    <Mail size={18} className="company-config__input-icon" />
                    <input
                      type="email"
                      value={companyData.email}
                      onChange={(e) => handleInputChange('email', e.target.value)}
                      className="company-config__input"
                      placeholder="contact@entreprise.com"
                    />
                  </div>
                </div>
  
                <div className="company-config__input-group">
                  <label className="company-config__label">Téléphone</label>
                  <div className="company-config__input-wrapper">
                    <Phone size={18} className="company-config__input-icon" />
                    <input
                      type="tel"
                      value={companyData.phone}
                      onChange={(e) => handleInputChange('phone', e.target.value)}
                      className="company-config__input"
                      placeholder="+261 XX XX XXX XX"
                    />
                  </div>
                </div>
  
                <div className="company-config__input-group">
                  <label className="company-config__label">Adresse</label>
                  <div className="company-config__input-wrapper">
                    <MapPin size={18} className="company-config__input-icon" />
                    <input
                      type="text"
                      value={companyData.address}
                      onChange={(e) => handleInputChange('address', e.target.value)}
                      className="company-config__input"
                      placeholder="Adresse complète"
                    />
                  </div>
                </div>

                <div className="company-config__input-group">
                  <label className="company-config__label">Signature sur les factures</label>
                  <div className="company-config__input-wrapper">
                    <Signature size={18} className="company-config__input-icon" />
                    <input
                      type="text"
                      value={companyData.invoice_signature}
                      onChange={(e) => handleInputChange('invoice_signature', e.target.value)}
                      className="company-config__input"
                      placeholder="signature ou une phrase courte"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          {/* Save Button */}
          <div className="company-config__footer">
            <button 
              onClick={handleSave} 
              className="company-config__save-btn"
              disabled={isSaving}
            >
              {isSaving ? (
                <Loader2 size={18} className="company-config__spinner" />
              ) : (
                <Save size={18} />
              )}
              <span>{isSaving ? 'Enregistrement...' : 'Enregistrer les modifications'}</span>
            </button>
          </div>
        </div>
      </div>
    );
  };
  
  export default CompanyConfiguration;