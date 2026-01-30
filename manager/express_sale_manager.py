#!/usr/bin/env python3
"""
Express Sale - Gestionnaire Docker
Interface améliorée pour démarrer/arrêter l'application
"""
import tkinter as tk
from tkinter import messagebox, scrolledtext, ttk
import subprocess
import os
from pathlib import Path


class ExpressSaleManager:
    def __init__(self, root):
        self.root = root
        self.root.title("Express Sale - Gestionnaire")
        self.root.geometry("900x700")
        self.root.resizable(True, True)
        
        # Déterminer le répertoire du projet
        self.project_dir = Path(__file__).parent.absolute()
        self.logo_path = self.project_dir / "logo.png"
        
        # Couleurs du thème
        self.colors = {
            'bg': '#f5f5f5',
            'card': '#ffffff',
            'primary': '#4CAF50',
            'danger': '#f44336',
            'warning': '#FF9800',
            'info': '#2196F3',
            'purple': '#9C27B0',
            'tailscale': '#5C6BC0',
            'text': '#333333',
            'text_light': '#666666',
            'border': '#e0e0e0'
        }
        
        self.root.configure(bg=self.colors['bg'])
        
        # Créer l'icône de la fenêtre
        self.create_window_icon()
        
        # Créer l'interface
        self.create_ui()
        
        # Vérifications initiales
        self.check_printer_status()
        self.update_tailscale_buttons()
        
        # Mise à jour périodique
        self.root.after(1000, self.periodic_update)
    
    def create_window_icon(self):
        """Créer une icône pour la fenêtre"""
        try:
            if self.logo_path.exists():
                from PIL import Image, ImageTk
                img = Image.open(self.logo_path)
                img.thumbnail((64, 64), Image.Resampling.LANCZOS)
                photo = ImageTk.PhotoImage(img)
                self.root.iconphoto(True, photo)
                self.root._icon_image = photo
                return
        except:
            pass
        
        try:
            from PIL import Image, ImageDraw, ImageTk
            img = Image.new('RGB', (64, 64), color='#4CAF50')
            draw = ImageDraw.Draw(img)
            draw.ellipse([4, 4, 60, 60], fill='#4CAF50', outline='#2E7D32')
            draw.rectangle([18, 15, 46, 28], fill='#00E676')
            for i in range(3):
                for j in range(3):
                    x = 18 + j * 10
                    y = 32 + i * 8
                    draw.rectangle([x, y, x+7, y+5], fill='white')
            photo = ImageTk.PhotoImage(img)
            self.root.iconphoto(True, photo)
            self.root._icon_image = photo
        except:
            pass
    
    def create_ui(self):
        """Créer l'interface utilisateur"""
        # Conteneur principal avec scroll
        main_container = tk.Frame(self.root, bg=self.colors['bg'])
        main_container.pack(fill='both', expand=True, padx=10, pady=10)
        
        # Canvas avec scrollbar
        canvas = tk.Canvas(main_container, bg=self.colors['bg'], highlightthickness=0)
        scrollbar = tk.Scrollbar(main_container, orient='vertical', command=canvas.yview)
        
        self.scrollable_frame = tk.Frame(canvas, bg=self.colors['bg'])
        self.scrollable_frame.bind(
            '<Configure>',
            lambda e: canvas.configure(scrollregion=canvas.bbox('all'))
        )
        
        canvas.create_window((0, 0), window=self.scrollable_frame, anchor='nw', width=860)
        canvas.configure(yscrollcommand=scrollbar.set)
        
        # Header
        self.create_header()
        
        # Section Docker
        self.create_docker_section()
        
        # Section Tailscale
        self.create_tailscale_section()
        
        # Section Informations de connexion
        self.create_connection_info_section()
        
        # Section Logs
        self.create_logs_section()
        
        # Pack canvas et scrollbar
        canvas.pack(side='left', fill='both', expand=True)
        scrollbar.pack(side='right', fill='y')
        
        # Mouse wheel scroll
        def _on_mousewheel(event):
            canvas.yview_scroll(int(-1*(event.delta/120)), 'units')
        canvas.bind_all('<MouseWheel>', _on_mousewheel)
        canvas.bind_all('<Button-4>', lambda e: canvas.yview_scroll(-1, 'units'))
        canvas.bind_all('<Button-5>', lambda e: canvas.yview_scroll(1, 'units'))
    
    def create_card(self, parent):
        """Créer un cadre type carte"""
        card = tk.Frame(parent, bg=self.colors['card'], relief='flat', bd=0)
        card.pack(fill='x', pady=(0, 15))
        
        # Effet d'ombre (simulé avec un cadre gris)
        shadow = tk.Frame(parent, bg='#d0d0d0', height=2)
        shadow.place(in_=card, relx=0.02, rely=1, relwidth=0.96, height=2)
        
        return card
    
    def create_section_title(self, parent, text, icon=''):
        """Créer un titre de section"""
        title_text = f'{icon} {text}' if icon else text
        title = tk.Label(
            parent,
            text=title_text,
            font=('Arial', 12, 'bold'),
            bg=self.colors['card'],
            fg=self.colors['text'],
            anchor='w'
        )
        title.pack(fill='x', padx=20, pady=(15, 10))
        
        # Ligne de séparation
        separator = tk.Frame(parent, bg=self.colors['border'], height=1)
        separator.pack(fill='x', padx=20, pady=(0, 10))
    
    def create_button(self, parent, text, command, color, width=None):
        """Créer un bouton stylisé"""
        btn = tk.Button(
            parent,
            text=text,
            command=command,
            bg=color,
            fg='white',
            font=('Arial', 10, 'bold'),
            relief='flat',
            cursor='hand2',
            padx=15,
            pady=10,
            width=width or 20
        )
        
        # Effet hover
        def on_enter(e):
            btn['bg'] = self.lighten_color(color)
        
        def on_leave(e):
            btn['bg'] = color
        
        btn.bind('<Enter>', on_enter)
        btn.bind('<Leave>', on_leave)
        
        return btn
    
    def lighten_color(self, color):
        """Éclaircir une couleur hexadécimale"""
        # Simple éclaircissement en ajoutant 20 à chaque composante
        if color.startswith('#'):
            r = min(255, int(color[1:3], 16) + 20)
            g = min(255, int(color[3:5], 16) + 20)
            b = min(255, int(color[5:7], 16) + 20)
            return f'#{r:02x}{g:02x}{b:02x}'
        return color
    
    def create_header(self):
        """Créer l'en-tête"""
        header_card = self.create_card(self.scrollable_frame)
        
        header_content = tk.Frame(header_card, bg=self.colors['card'])
        header_content.pack(fill='x', padx=20, pady=20)
        
        # Logo
        logo_frame = tk.Frame(header_content, bg=self.colors['card'])
        logo_frame.pack(side='left', padx=(0, 20))
        self.load_logo(logo_frame)
        
        # Titre et description
        text_frame = tk.Frame(header_content, bg=self.colors['card'])
        text_frame.pack(side='left', fill='both', expand=True)
        
        title = tk.Label(
            text_frame,
            text='Express Sale Manager',
            font=('Arial', 20, 'bold'),
            bg=self.colors['card'],
            fg=self.colors['primary']
        )
        title.pack(anchor='w')
        
        subtitle = tk.Label(
            text_frame,
            text='Gérez votre application de caisse enregistreuse',
            font=('Arial', 10),
            bg=self.colors['card'],
            fg=self.colors['text_light']
        )
        subtitle.pack(anchor='w', pady=(5, 0))
    
    def load_logo(self, parent):
        """Charger et afficher le logo"""
        try:
            if self.logo_path.exists():
                from PIL import Image, ImageTk
                img = Image.open(self.logo_path)
                img.thumbnail((70, 70), Image.Resampling.LANCZOS)
                photo = ImageTk.PhotoImage(img)
                logo_label = tk.Label(parent, image=photo, bg=self.colors['card'])
                logo_label.image = photo
                logo_label.pack()
                return
        except:
            pass
        
        # Logo par défaut
        logo_canvas = tk.Canvas(parent, width=70, height=70, bg=self.colors['card'], highlightthickness=0)
        logo_canvas.pack()
        logo_canvas.create_oval(5, 5, 65, 65, fill=self.colors['primary'], outline='#2E7D32', width=2)
        logo_canvas.create_rectangle(22, 18, 48, 30, fill='#1B5E20')
        logo_canvas.create_rectangle(24, 20, 46, 28, fill='#00E676')
        for i in range(3):
            for j in range(3):
                x = 22 + j * 9
                y = 35 + i * 7
                logo_canvas.create_rectangle(x, y, x+6, y+4, fill='white')
        logo_canvas.create_text(35, 24, text='$', fill='white', font=('Arial', 10, 'bold'))
    
    def create_docker_section(self):
        """Créer la section Docker"""
        card = self.create_card(self.scrollable_frame)
        self.create_section_title(card, 'Gestion Docker', '🐳')
        
        # Conteneur des boutons
        btn_container = tk.Frame(card, bg=self.colors['card'])
        btn_container.pack(fill='x', padx=20, pady=(0, 15))
        
        # Ligne 1: Démarrage
        row1 = tk.Frame(btn_container, bg=self.colors['card'])
        row1.pack(fill='x', pady=(0, 10))
        
        btn1 = self.create_button(row1, '🖨️  Démarrer AVEC imprimante', 
                                  self.start_with_printer, self.colors['primary'])
        btn1.pack(side='left', padx=(0, 10), expand=True, fill='x')
        
        btn2 = self.create_button(row1, '✓  Démarrer SANS imprimante', 
                                  self.start_without_printer, self.colors['info'])
        btn2.pack(side='left', expand=True, fill='x')
        
        # Ligne 2: Contrôles
        row2 = tk.Frame(btn_container, bg=self.colors['card'])
        row2.pack(fill='x', pady=(0, 10))
        
        btn3 = self.create_button(row2, '⏹  Arrêter', 
                                  self.stop_containers, self.colors['danger'], width=18)
        btn3.pack(side='left', padx=(0, 10))
        
        btn4 = self.create_button(row2, '🔄  Redémarrer', 
                                  self.restart_containers, self.colors['warning'], width=18)
        btn4.pack(side='left', padx=(0, 10))
        
        self.btn_status = self.create_button(row2, '📊  Voir le statut', 
                                             self.show_status, self.colors['purple'], width=18)
        self.btn_status.pack(side='left')
    
    def create_tailscale_section(self):
        """Créer la section Tailscale"""
        card = self.create_card(self.scrollable_frame)
        self.create_section_title(card, 'Accès à Distance - Tailscale', '🌐')
        
        # Conteneur des boutons
        btn_container = tk.Frame(card, bg=self.colors['card'])
        btn_container.pack(fill='x', padx=20, pady=(0, 15))
        
        # Ligne 1: Contrôles principaux
        row1 = tk.Frame(btn_container, bg=self.colors['card'])
        row1.pack(fill='x', pady=(0, 10))
        
        self.btn_tailscale_up = self.create_button(row1, '▲  Activer', 
                                                   self.tailscale_up, self.colors['tailscale'], width=15)
        self.btn_tailscale_up.pack(side='left', padx=(0, 10))
        
        self.btn_tailscale_down = self.create_button(row1, '▼  Désactiver', 
                                                     self.tailscale_down, '#78909C', width=15)
        self.btn_tailscale_down.pack(side='left', padx=(0, 10))
        
        self.btn_tailscale_web = self.create_button(row1, '🌐  Admin Web', 
                                                    self.tailscale_web_admin, '#9575CD', width=15)
        self.btn_tailscale_web.pack(side='left')
        
        # Ligne 2: Informations
        row2 = tk.Frame(btn_container, bg=self.colors['card'])
        row2.pack(fill='x')
        
        self.btn_tailscale_status = self.create_button(row2, '📡  Statut', 
                                                       self.tailscale_status, '#26A69A', width=22)
        self.btn_tailscale_status.pack(side='left', padx=(0, 10))
        
        self.btn_tailscale_ip = self.create_button(row2, '📍  Obtenir IP', 
                                                   self.tailscale_get_ip, '#26A69A', width=22)
        self.btn_tailscale_ip.pack(side='left')
    
    def create_connection_info_section(self):
        """Créer la section d'informations de connexion"""
        card = self.create_card(self.scrollable_frame)
        self.create_section_title(card, 'Informations de Connexion', '🔗')
        
        # Zone de texte avec scroll
        text_container = tk.Frame(card, bg=self.colors['card'])
        text_container.pack(fill='both', padx=20, pady=(0, 15))
        
        # Créer un Text widget au lieu de Label pour meilleur contrôle
        self.info_text = tk.Text(
            text_container,
            height=12,
            font=('Courier', 9),
            bg='#fafafa',
            fg=self.colors['text'],
            relief='solid',
            bd=1,
            wrap='word',
            padx=10,
            pady=10
        )
        self.info_text.pack(side='left', fill='both', expand=True)
        
        info_scroll = tk.Scrollbar(text_container, command=self.info_text.yview)
        info_scroll.pack(side='right', fill='y')
        self.info_text.config(yscrollcommand=info_scroll.set)
        
        # Texte initial
        self.info_text.insert('1.0', 'Démarrez l\'application pour voir les adresses de connexion')
        self.info_text.config(state='disabled')
    
    def create_logs_section(self):
        """Créer la section des logs"""
        card = self.create_card(self.scrollable_frame)
        self.create_section_title(card, 'Journal d\'Activité', '📝')
        
        log_container = tk.Frame(card, bg=self.colors['card'])
        log_container.pack(fill='both', padx=20, pady=(0, 15))
        
        self.log_text = scrolledtext.ScrolledText(
            log_container,
            height=8,
            font=('Courier', 9),
            bg='#1e1e1e',
            fg='#00ff00',
            state='disabled',
            relief='solid',
            bd=1
        )
        self.log_text.pack(fill='both', expand=True)
    
    def periodic_update(self):
        """Mise à jour périodique"""
        self.update_tailscale_buttons()
        self.root.after(5000, self.periodic_update)
    
    def update_tailscale_buttons(self):
        """Mettre à jour l'état des boutons Tailscale"""
        status = self.check_tailscale_status()
        
        status_map = {
            'not_installed': ('❌ Non installé', 'disabled', '#999999'),
            'stopped': ('⏸️ Arrêté', 'normal', self.colors['tailscale']),
            'running': ('🔄 En cours', 'normal', self.colors['warning']),
            'running_not_connected': ('🔗 Non connecté', 'normal', self.colors['warning']),
            'connected': ('✓ Connecté', 'normal', self.colors['primary']),
            'connected_privileged': ('✓ Connecté', 'normal', self.colors['primary']),
        }
        
        status_text, btn_state, btn_color = status_map.get(status, ('❓ Inconnu', 'normal', '#999999'))
        
        self.btn_tailscale_up.config(text=f'▲  {status_text}', bg=btn_color, state=btn_state)
        
        # Gérer les autres boutons
        if status in ['not_installed', 'unknown']:
            self.btn_tailscale_down.config(state='disabled')
            self.btn_tailscale_status.config(state='disabled')
            self.btn_tailscale_ip.config(state='disabled')
            self.btn_tailscale_web.config(state='disabled')
        elif status in ['stopped', 'service_inactive']:
            self.btn_tailscale_down.config(state='disabled')
            self.btn_tailscale_status.config(state='normal')
            self.btn_tailscale_ip.config(state='normal')
            self.btn_tailscale_web.config(state='normal')
        else:
            self.btn_tailscale_down.config(state='normal')
            self.btn_tailscale_status.config(state='normal')
            self.btn_tailscale_ip.config(state='normal')
            self.btn_tailscale_web.config(state='normal')
    
    def check_tailscale_status(self):
        """Vérifier le statut de Tailscale"""
        try:
            which_result = subprocess.run(['which', 'tailscale'], capture_output=True, text=True)
            if which_result.returncode != 0:
                return 'not_installed'
            
            try:
                systemctl_result = subprocess.run(
                    ['systemctl', 'is-active', 'tailscaled'],
                    capture_output=True, text=True
                )
                if systemctl_result.returncode == 0 and 'active' in systemctl_result.stdout.lower():
                    status_result = subprocess.run(['tailscale', 'status'], 
                                                  capture_output=True, text=True, timeout=5)
                    if status_result.returncode == 0:
                        output = status_result.stdout.lower()
                        if 'stopped' in output:
                            return 'stopped'
                        elif 'logged in' in output or 'active' in output:
                            ip_result = subprocess.run(['tailscale', 'ip', '-4'], 
                                                      capture_output=True, text=True)
                            if ip_result.returncode == 0 and ip_result.stdout.strip():
                                return 'connected'
                            return 'running_not_connected'
                    return 'running'
                return 'stopped'
            except:
                return 'unknown'
        except:
            return 'not_installed'
    
    def log(self, message):
        """Ajouter un message dans les logs"""
        self.log_text.config(state='normal')
        self.log_text.insert(tk.END, f'{message}\n')
        self.log_text.see(tk.END)
        self.log_text.config(state='disabled')
    
    def check_printer_status(self):
        """Vérifier si l'imprimante est connectée"""
        printer_exists = os.path.exists('/dev/usb/lp0')
        if printer_exists:
            self.log('✅ Imprimante détectée sur /dev/usb/lp0')
        else:
            self.log('⚠️  Aucune imprimante détectée sur /dev/usb/lp0')
        return printer_exists
    
    def get_network_ips(self):
        """Récupérer toutes les adresses IP réseau"""
        ips = []
        try:
            result = subprocess.run(['hostname', '-I'], capture_output=True, text=True)
            if result.returncode == 0:
                ips = result.stdout.strip().split()
        except:
            pass
        return ips
    
    def generate_qr_code(self, url):
        """Générer un QR code pour une URL (version compacte)"""
        try:
            import qrcode
            qr = qrcode.QRCode(version=1, box_size=2, border=1)
            qr.add_data(url)
            qr.make(fit=True)
            
            qr_ascii = []
            matrix = qr.get_matrix()
            for row in matrix:
                line = ''.join(['██' if cell else '  ' for cell in row])
                qr_ascii.append(line)
            return '\n'.join(qr_ascii)
        except ImportError:
            return None
    
    def update_connection_info(self):
        """Mettre à jour les informations de connexion"""
        self.info_text.config(state='normal')
        self.info_text.delete('1.0', tk.END)
        
        info_lines = []
        info_lines.append('═══════════════════════════════════════════════════')
        info_lines.append('📱 ACCÈS À L\'APPLICATION')
        info_lines.append('═══════════════════════════════════════════════════\n')
        
        info_lines.append('🏠 ACCÈS LOCAL (sur cet ordinateur):')
        info_lines.append('   → http://localhost:3000\n')
        
        # IPs réseau
        ips = self.get_network_ips()
        if ips:
            info_lines.append('📶 ACCÈS RÉSEAU LOCAL:')
            primary_ip = None
            for ip in ips:
                if not ip.startswith('172.'):
                    info_lines.append(f'   → http://{ip}:3000')
                    if primary_ip is None:
                        primary_ip = ip
            
            if primary_ip:
                url = f'http://{primary_ip}:3000'
                qr_code = self.generate_qr_code(url)
                if qr_code:
                    info_lines.append('\n📲 QR Code Réseau Local:\n')
                    info_lines.append(qr_code)
        
        # Tailscale IP
        tailscale_ip = self.get_tailscale_ip()
        if tailscale_ip:
            info_lines.append('\n' + '─' * 51)
            info_lines.append('🌐 ACCÈS VIA TAILSCALE (à distance):')
            info_lines.append(f'   → http://{tailscale_ip}:3000')
            
            url_ts = f'http://{tailscale_ip}:3000'
            qr_code_ts = self.generate_qr_code(url_ts)
            if qr_code_ts:
                info_lines.append('\n📲 QR Code Tailscale:\n')
                info_lines.append(qr_code_ts)
        else:
            info_lines.append('\n' + '─' * 51)
            info_lines.append('⚠️  Tailscale non connecté')
            info_lines.append('   (Activez-le pour l\'accès à distance)')
        
        info_lines.append('\n' + '═' * 51)
        info_lines.append('ℹ️  Backend API: http://localhost:8000')
        info_lines.append('═' * 51)
        
        self.info_text.insert('1.0', '\n'.join(info_lines))
        self.info_text.config(state='disabled')
    
    def get_tailscale_ip(self):
        """Obtenir l'IP Tailscale"""
        try:
            result = subprocess.run(['tailscale', 'ip', '-4'], capture_output=True, text=True)
            if result.returncode == 0 and result.stdout.strip():
                return result.stdout.strip()
            
            result = subprocess.run(['pkexec', 'tailscale', 'ip', '-4'], 
                                   capture_output=True, text=True)
            if result.returncode == 0 and result.stdout.strip():
                return result.stdout.strip()
        except:
            pass
        return None
    
    def run_command(self, command, show_output=True, need_sudo=False):
        """Exécuter une commande shell"""
        try:
            self.log(f'🔧 Exécution: {command}')
            
            if need_sudo:
                command = f'pkexec {command}'
            
            result = subprocess.run(
                command, shell=True, cwd=self.project_dir,
                capture_output=True, text=True
            )
            
            if show_output:
                for line in result.stdout.split('\n'):
                    if line.strip():
                        self.log(f'  {line}')
                for line in result.stderr.split('\n'):
                    if line.strip():
                        self.log(f'⚠️  {line}')
            
            return result.returncode == 0
        except Exception as e:
            self.log(f'❌ Erreur: {str(e)}')
            return False
    
    def start_with_printer(self):
        """Démarrer avec l'imprimante"""
        if not self.check_printer_status():
            if not messagebox.askyesno('Imprimante non détectée', 
                                       'L\'imprimante n\'est pas détectée.\nVoulez-vous quand même démarrer ?'):
                return
        
        self.log('🖨️  Démarrage AVEC imprimante...')
        success = self.run_command('docker compose -f docker-compose.yml -f docker-compose.printer.yml up -d')
        
        if success:
            messagebox.showinfo('Succès', '✅ Application démarrée avec imprimante!')
            self.update_connection_info()
        else:
            messagebox.showerror('Erreur', '❌ Échec du démarrage')
    
    def start_without_printer(self):
        """Démarrer sans l'imprimante"""
        self.log('✅ Démarrage SANS imprimante...')
        success = self.run_command('docker compose -f docker-compose.yml up -d')
        
        if success:
            messagebox.showinfo('Succès', '✅ Application démarrée sans imprimante!')
            self.update_connection_info()
        else:
            messagebox.showerror('Erreur', '❌ Échec du démarrage')
    
    def stop_containers(self):
        """Arrêter les containers"""
        if messagebox.askyesno('Confirmation', 'Voulez-vous vraiment arrêter l\'application ?'):
            self.log('⏹️  Arrêt de l\'application...')
            success = self.run_command('docker compose down')
            
            if success:
                messagebox.showinfo('Succès', '✅ Application arrêtée!')
                self.info_text.config(state='normal')
                self.info_text.delete('1.0', tk.END)
                self.info_text.insert('1.0', 'Démarrez l\'application pour voir les adresses de connexion')
                self.info_text.config(state='disabled')
            else:
                messagebox.showerror('Erreur', '❌ Échec de l\'arrêt')
    
    def restart_containers(self):
        """Redémarrer les containers"""
        self.log('🔄 Redémarrage de l\'application...')
        success = self.run_command('docker compose restart')
        
        if success:
            messagebox.showinfo('Succès', '✅ Application redémarrée!')
            self.update_connection_info()
        else:
            messagebox.showerror('Erreur', '❌ Échec du redémarrage')
    
    def show_status(self):
        """Afficher le statut des containers"""
        self.log('📊 Récupération du statut Docker...')
        self.run_command('docker compose ps', show_output=True)
    
    def tailscale_up(self):
        """Activer Tailscale"""
        if messagebox.askyesno('Activer Tailscale',
                              'Voulez-vous activer Tailscale pour l\'accès à distance ?\n\n'
                              'Une fenêtre s\'ouvrira pour vous connecter.'):
            self.log('🌐 Activation de Tailscale...')
            success = self.run_command('tailscale up', show_output=True, need_sudo=True)
            
            if success:
                messagebox.showinfo('Succès', '✅ Tailscale activé!')
                self.log('✅ Tailscale activé avec succès')
            else:
                messagebox.showerror('Erreur', '❌ Échec de l\'activation de Tailscale')
            
            self.update_tailscale_buttons()
            self.update_connection_info()
    
    def tailscale_down(self):
        """Désactiver Tailscale"""
        if messagebox.askyesno('Désactiver Tailscale',
                              'Voulez-vous désactiver Tailscale ?\n\n'
                              'L\'accès à distance ne sera plus disponible.'):
            self.log('🌐 Désactivation de Tailscale...')
            success = self.run_command('tailscale down', show_output=True, need_sudo=True)
            
            if success:
                messagebox.showinfo('Succès', '✅ Tailscale désactivé!')
                self.log('✅ Tailscale désactivé avec succès')
            else:
                messagebox.showerror('Erreur', '❌ Échec de la désactivation de Tailscale')
            
            self.update_tailscale_buttons()
            self.update_connection_info()
    
    def tailscale_status(self):
        """Afficher le statut Tailscale"""
        self.log('📡 Récupération du statut Tailscale...')
        self.run_command('tailscale status', show_output=True, need_sudo=True)
    
    def tailscale_get_ip(self):
        """Obtenir et afficher l'IP Tailscale"""
        self.log('🔍 Récupération de l\'IP Tailscale...')
        ip = self.get_tailscale_ip()
        
        if ip:
            self.log(f'✅ IP Tailscale: {ip}')
            messagebox.showinfo('IP Tailscale', f'Votre adresse Tailscale est:\n\n{ip}\n\nAccès: http://{ip}:3000')
        else:
            self.log('⚠️  Aucune IP Tailscale disponible')
            messagebox.showwarning('IP Tailscale', 'Aucune adresse Tailscale disponible.\n\nVérifiez que Tailscale est connecté.')
    
    def tailscale_web_admin(self):
        """Ouvrir l'interface d'administration web de Tailscale"""
        self.log('🌐 Ouverture de l\'admin Tailscale...')
        try:
            subprocess.Popen(['xdg-open', 'https://login.tailscale.com/admin/machines'])
            self.log('✅ Interface d\'admin Tailscale ouverte')
        except Exception as e:
            self.log(f'❌ Erreur: {str(e)}')
            messagebox.showerror('Erreur', 'Impossible d\'ouvrir l\'interface d\'administration Tailscale.')


def main():
    root = tk.Tk()
    app = ExpressSaleManager(root)
    root.mainloop()


if __name__ == '__main__':
    main()
