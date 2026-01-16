import { useState } from 'react';
import { X, Plus, AlertCircle, Trash2 } from 'lucide-react';
import '../../styles/AttributeModal.css';

const AttributeModal = ({ isOpen, onClose, onSubmit }) => {
  const [formData, setFormData] = useState({
    name: '',
    display_name: '',
    input_type: 'select',
    attribute_values: []
  });

  const [newValue, setNewValue] = useState('');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));

    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  // ➕ Ajouter une valeur
  const addValue = () => {
    if (!newValue.trim()) return;

    if (
      formData.attribute_values.some(v => v.value.toLowerCase() === newValue.toLowerCase())
    ) {
      return;
    }

    setFormData(prev => ({
      ...prev,
      attribute_values: [
        ...prev.attribute_values,
        { value: newValue.trim(), sort_order: prev.attribute_values.length + 1 }
      ]
    }));

    setNewValue('');
  };

  // ❌ Supprimer une valeur
  const removeValue = (index) => {
    const updated = formData.attribute_values.filter((_, i) => i !== index)
      .map((v, i) => ({ ...v, sort_order: i + 1 }));

    setFormData(prev => ({
      ...prev,
      attribute_values: updated
    }));
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Nom technique requis';
    } else if (!/^[a-z_]+$/.test(formData.name)) {
      newErrors.name = 'Uniquement lettres minuscules et underscores';
    }

    if (!formData.display_name.trim()) {
      newErrors.display_name = 'Nom d\'affichage requis';
    }

    if (
      ['select', 'multiselect'].includes(formData.input_type) &&
      formData.attribute_values.length === 0
    ) {
      newErrors.attribute_values = 'Ajoutez au moins une valeur possible';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;

    setLoading(true);
    try {
      console.log('📤 Soumission attribut:', formData);
      await onSubmit(formData);
      handleClose();
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setFormData({
      name: '',
      display_name: '',
      input_type: 'select',
      attribute_values: []
    });
    setNewValue('');
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  const showValuesSection = ['select'].includes(formData.input_type);

  return (
    <div className="attribute-modal-overlay" onClick={handleClose}>
      <div className="attribute-modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="attribute-modal-header">
          <h2 className="attribute-modal-title">
            <Plus size={24} />
            Nouvel attribut
          </h2>
          <button className="attribute-modal-close" onClick={handleClose}>
            <X size={20} />
          </button>
        </div>

        <div className="attribute-modal-body">

          {/* NOM TECHNIQUE */}
          <div className="attribute-input-group">
            <label className="attribute-label">
              Nom technique *
              <span className="attribute-helper">Ex: size, color, material</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              className={`attribute-input ${errors.name ? 'attribute-input-error' : ''}`}
            />
            {errors.name && (
              <span className="attribute-error-text">
                <AlertCircle size={14} /> {errors.name}
              </span>
            )}
          </div>

          {/* NOM D'AFFICHAGE */}
          <div className="attribute-input-group">
            <label className="attribute-label">
              Nom d'affichage *
            </label>
            <input
              type="text"
              name="display_name"
              value={formData.display_name}
              onChange={handleChange}
              className={`attribute-input ${errors.display_name ? 'attribute-input-error' : ''}`}
            />
            {errors.display_name && (
              <span className="attribute-error-text">
                <AlertCircle size={14} /> {errors.display_name}
              </span>
            )}
          </div>

          {/* TYPE */}
          <div className="attribute-input-group">
            <label className="attribute-label">Type de saisie *</label>
            <select
              name="input_type"
              value={formData.input_type}
              onChange={handleChange}
              className="attribute-input"
            >
              <option value="select">Liste déroulante</option>
              <option value="text">Texte libre</option>
              <option value="number">Nombre</option>
            </select>
          </div>

          {/* ===================== */}
          {/* VALEURS POSSIBLES */}
          {/* ===================== */}
          {showValuesSection && (
            <div className="attribute-values-box">

              <div className="attribute-values-header">
                <label className="attribute-label">
                  Valeurs possibles
                  <span className="attribute-helper">
                    Utilisées pour créer les variantes
                  </span>
                </label>
              </div>

              <div className="attribute-values-add">
                <input
                  type="text"
                  className="attribute-input"
                  placeholder="Ex: S, M, L, Rouge..."
                  value={newValue}
                  onChange={(e) => setNewValue(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && addValue()}
                />
                <button
                  type="button"
                  className="attribute-btn attribute-btn-primary"
                  onClick={addValue}
                >
                  <Plus size={16} />
                  Ajouter
                </button>
              </div>

              {errors.attribute_values && (
                <div className="attribute-error-text">
                  <AlertCircle size={14} />
                  {errors.attribute_values}
                </div>
              )}

              <div className="attribute-values-list">
                {formData.attribute_values.map((item, index) => (
                  <div key={index} className="attribute-value-item">
                    <span>{item.value}</span>
                    <button
                      type="button"
                      className="attribute-value-remove"
                      onClick={() => removeValue(index)}
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="attribute-modal-actions">
            <button
              type="button"
              className="attribute-btn attribute-btn-secondary"
              onClick={handleClose}
              disabled={loading}
            >
              Annuler
            </button>
            <button
              type="button"
              className="attribute-btn attribute-btn-primary"
              onClick={handleSubmit}
              disabled={loading}
            >
              {loading ? 'Création...' : 'Créer l\'attribut'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AttributeModal;
