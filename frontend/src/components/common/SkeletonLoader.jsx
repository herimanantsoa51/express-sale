import React from 'react';
import '../../styles/SkeletonLoader.css';

const SkeletonLoader = ({
  type = 'default',
  count = 1,
  className = ''
}) => {
  const skeletons = Array.from({ length: count }, (_, index) => {
    const baseClass = 'skeleton';
    const typeClass = `skeleton-${type}`;
    
    const skeletonClasses = [
      baseClass,
      typeClass,
      className
    ].filter(Boolean).join(' ');

    return (
      <div key={index} className={skeletonClasses}>
        <div className="skeleton-shimmer" />
      </div>
    );
  });

  return <>{skeletons}</>;
};

export default SkeletonLoader;