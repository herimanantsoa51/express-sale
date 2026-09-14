import React, { useState, useEffect } from 'react';
import usersService from '../../services/usersService';
import '../../styles/Users.css';

// Icônes SVG
const PlusIcon = () => (
  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M10 4v12M4 10h12" strokeLinecap="round" />
  </svg>
);

const EditIcon = () => (
  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M12.5 2.5l3 3L6 15H3v-3L12.5 2.5z" strokeLinecap="round" strokeLinejoin="round" />
  </svg>
);

const CloseIcon = () => (
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M18 6L6 18M6 6l12 12" strokeLinecap="round" />
  </svg>
);

const UsersIcon = () => (
  <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" strokeLinecap="round" strokeLinejoin="round" />
  </svg>
);

const UsersPage = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState('create'); // 'create' ou 'edit'
  const [selectedUser, setSelectedUser] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    username: '',
    password: '',
    role: 'vendeur',
    is_active: true
  });

  // Charger les utilisateurs
  useEffect(() => {
    loadUsers();
  }, []);

  const loadUsers = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await usersService.getAllUsers();
      setUsers(data);
    } catch (err) {
      setError('Erreur lors du chargement des utilisateurs');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Ouvrir modal création
  const handleOpenCreate = () => {
    setModalMode('create');
    setFormData({
      name: '',
      username: '',
      password: '',
      role: 'vendeur',
      is_active: true
    });
    setShowModal(true);
  };

  // Ouvrir modal édition
  const handleOpenEdit = (user) => {
    setModalMode('edit');
    setSelectedUser(user);
    setFormData({
      name: user.name,
      username: user.username,
      password: '',
      role: user.role,
      is_active: user.is_active
    });
    setShowModal(true);
  };

  // Fermer modal
  const handleCloseModal = () => {
    setShowModal(false);
    setSelectedUser(null);
    setFormData({
      name: '',
      username: '',
      password: '',
      role: 'vendeur',
      is_active: true
    });
  };

  // Gérer changement input
  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  // Soumettre formulaire
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    try {
      if (modalMode === 'create') {
        await usersService.createUser(formData);
      } else {
        // Pour l'édition, on n'envoie le mot de passe que s'il est rempli
        const updateData = { ...formData };
        if (!updateData.password) {
          delete updateData.password;
        }
        // Ne pas envoyer username en édition
        delete updateData.username;
        
        await usersService.updateUser(selectedUser.id, updateData);
      }
      
      handleCloseModal();
      loadUsers();
    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de la sauvegarde');
      console.error(err);
    }
  };

  // Toggle statut utilisateur
  const handleToggleStatus = async (userId) => {
    try {
      await usersService.updateUserStatus(userId);
      loadUsers();
    } catch (err) {
      setError('Erreur lors du changement de statut');
      console.error(err);
    }
  };

  // Formater date
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <div className="users-page">
      <div className="users-container">
        {/* Header */}
        <div className="users-header scale-in">
          <h1 className="users-header-title">Gestion des utilisateurs</h1>
          <div className="users-header-actions">
            <button className="btn btn-primary" onClick={handleOpenCreate}>
              <PlusIcon />
              Nouvel utilisateur
            </button>
          </div>
        </div>

        {/* Erreur */}
        {error && (
          <div className="alert alert-error fade-in">
            {error}
          </div>
        )}

        {/* Contenu */}
        {loading ? (
          <div className="loading-container">
            <div className="spinner"></div>
          </div>
        ) : users.length === 0 ? (
          <div className="users-table-container fade-in">
            <div className="empty-state">
              <div className="empty-state-icon">
                <UsersIcon />
              </div>
              <h2 className="empty-state-title">Aucun utilisateur</h2>
              <p className="empty-state-text">
                Commencez par créer votre premier utilisateur
              </p>
              <button className="btn btn-primary" onClick={handleOpenCreate}>
                <PlusIcon />
                Créer un utilisateur
              </button>
            </div>
          </div>
        ) : (
          <div className="users-table-container fade-in">
            <table className="users-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Nom d'utilisateur</th>
                  <th>Rôle</th>
                  <th>Statut</th>
                  <th>Date de création</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => (
                  <tr key={user.id}>
                    <td>
                      <strong>{user.name}</strong>
                    </td>
                    <td>{user.username}</td>
                    <td>
                      <span className={`badge badge-${user.role}`}>
                        {user.role === 'admin' ? 'Administrateur' : 'Vendeur'}
                      </span>
                    </td>
                    <td>
                      <span className={`badge ${user.is_active ? 'badge-active' : 'badge-inactive'}`}>
                        {user.is_active ? 'Actif' : 'Inactif'}
                      </span>
                    </td>
                    <td>{formatDate(user.created_at)}</td>
                    <td>
                      <div className="table-actions">
                        <button 
                          className="btn btn-icon" 
                          onClick={() => handleOpenEdit(user)}
                          title="Modifier"
                        >
                          <EditIcon />
                        </button>
                        <button 
                          className={`btn ${user.is_active ? 'btn-danger' : 'btn-success'}`}
                          onClick={() => handleToggleStatus(user.id)}
                          title={user.is_active ? 'Désactiver' : 'Activer'}
                        >
                          {user.is_active ? 'Désactiver' : 'Activer'}
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Modal */}
        {showModal && (
          <div className="modal-overlay" onClick={handleCloseModal}>
            <div className="modal scale-in" onClick={(e) => e.stopPropagation()}>
              <div className="modal-header">
                <h2 className="modal-title">
                  {modalMode === 'create' ? 'Nouvel utilisateur' : 'Modifier l\'utilisateur'}
                </h2>
                <button className="modal-close" onClick={handleCloseModal}>
                  <CloseIcon />
                </button>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="modal-body">
                  <div className="form-group">
                    <label className="form-label">Nom complet</label>
                    <input
                      type="text"
                      name="name"
                      className="form-input"
                      value={formData.name}
                      onChange={handleInputChange}
                      required
                      placeholder="Ex: Jean Dupont"
                    />
                  </div>

                  <div className="form-group">
                    <label className="form-label">Nom d'utilisateur</label>
                    <input
                      type="text"
                      name="username"
                      className="form-input"
                      value={formData.username}
                      onChange={handleInputChange}
                      required={modalMode === 'create'}
                      disabled={modalMode === 'edit'}
                      placeholder="Ex: jdupont"
                    />
                  </div>

                  <div className="form-group">
                    <label className="form-label">
                      Mot de passe {modalMode === 'edit' && '(laisser vide pour ne pas modifier)'}
                    </label>
                    <input
                      type="password"
                      name="password"
                      className="form-input"
                      value={formData.password}
                      onChange={handleInputChange}
                      required={modalMode === 'create'}
                      placeholder="••••••••"
                      minLength="4"
                    />
                  </div>

                  <div className="form-group">
                    <label className="form-label">Rôle</label>
                    <select
                      name="role"
                      className="form-select"
                      value={formData.role}
                      onChange={handleInputChange}
                      required
                    >
                      <option value="vendeur">Vendeur</option>
                      <option value="admin">Administrateur</option>
                    </select>
                  </div>

                  <div className="form-group">
                    <div className="form-checkbox-group">
                      <input
                        type="checkbox"
                        name="is_active"
                        className="form-checkbox"
                        checked={formData.is_active}
                        onChange={handleInputChange}
                        id="is_active"
                      />
                      <label htmlFor="is_active" className="form-label" style={{ marginBottom: 0 }}>
                        Compte actif
                      </label>
                    </div>
                  </div>
                </div>

                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={handleCloseModal}>
                    Annuler
                  </button>
                  <button type="submit" className="btn btn-primary">
                    {modalMode === 'create' ? 'Créer' : 'Enregistrer'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default UsersPage;