import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import '../../styles/Pagination.css';

const Pagination = ({
  currentPage,
  totalPages,
  onPageChange,
  showInfo = false,
  info,
  className = ''
}) => {
  if (totalPages <= 1) return null;

  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages) {
      onPageChange(page);
    }
  };

  const renderPageNumbers = () => {
    const pages = [];
    const delta = 2;
    const range = [];
    
    for (let i = Math.max(2, currentPage - delta); i <= Math.min(totalPages - 1, currentPage + delta); i++) {
      range.push(i);
    }
    
    if (currentPage - delta > 2) {
      range.unshift('...');
    }
    if (currentPage + delta < totalPages - 1) {
      range.push('...');
    }
    
    range.unshift(1);
    if (totalPages > 1) {
      range.push(totalPages);
    }
    
    return range.map((page, index) => {
      if (page === '...') {
        return (
          <span key={`ellipsis-${index}`} className="pagination-ellipsis">
            …
          </span>
        );
      }
      
      return (
        <button
          key={page}
          className={`pagination-page ${currentPage === page ? 'pagination-page-active' : ''}`}
          onClick={() => handlePageChange(page)}
          aria-label={`Page ${page}`}
          aria-current={currentPage === page ? 'page' : undefined}
        >
          {page}
        </button>
      );
    });
  };

  return (
    <div className={`pagination ${className}`}>
      {showInfo && info && (
        <div className="pagination-info">
          {info}
        </div>
      )}
      
      <div className="pagination-controls">
        <button
          className="pagination-arrow"
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage === 1}
          aria-label="Page précédente"
        >
          <ChevronLeft size={20} />
        </button>
        
        <div className="pagination-pages">
          {renderPageNumbers()}
        </div>
        
        <button
          className="pagination-arrow"
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage === totalPages}
          aria-label="Page suivante"
        >
          <ChevronRight size={20} />
        </button>
      </div>
    </div>
  );
};

export default Pagination;