const ActivityLogsPagination = ({ pagination, onPageChange }) => {
    const { current_page, last_page, from, to, total } = pagination;
  
    const getPageNumbers = () => {
      const pages = [];
      const maxVisible = 7;
      
      if (last_page <= maxVisible) {
        for (let i = 1; i <= last_page; i++) {
          pages.push(i);
        }
      } else {
        if (current_page <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(last_page);
        } else if (current_page >= last_page - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = last_page - 4; i <= last_page; i++) {
            pages.push(i);
          }
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current_page - 1; i <= current_page + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(last_page);
        }
      }
      
      return pages;
    };
  
    return (
      <div className="alp-container">
        <div className="alp-info">
          Affichage de {from} à {to} sur {total} entrées
        </div>
        
        <div className="alp-controls">
          <button
            className="alp-button alp-button-nav"
            onClick={() => onPageChange(current_page - 1)}
            disabled={current_page === 1}
          >
            ← Précédent
          </button>
  
          <div className="alp-pages">
            {getPageNumbers().map((page, index) => (
              page === '...' ? (
                <span key={`ellipsis-${index}`} className="alp-ellipsis">
                  ...
                </span>
              ) : (
                <button
                  key={page}
                  className={`alp-button alp-button-page ${
                    current_page === page ? 'alp-button-active' : ''
                  }`}
                  onClick={() => onPageChange(page)}
                >
                  {page}
                </button>
              )
            ))}
          </div>
  
          <button
            className="alp-button alp-button-nav"
            onClick={() => onPageChange(current_page + 1)}
            disabled={current_page === last_page}
          >
            Suivant →
          </button>
        </div>
      </div>
    );
  };
  
  export default ActivityLogsPagination;