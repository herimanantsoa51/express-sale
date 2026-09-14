# Supprimer le fichier .desktop
rm -f ~/.local/share/applications/ExpressSale.desktop ~/Bureau/ExpressSale.desktop 2>/dev/null

# Supprimer l'icône
rm -f ~/.local/share/icons/express-sale-logo.png 2>/dev/null

# Mettre à jour le cache
gtk-update-icon-cache -f -t ~/.local/share/icons
update-desktop-database ~/.local/share/applications

echo "✅ Désinstallation terminée"
