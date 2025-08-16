import React from 'react';
import { Program } from '../types';
import './ProgramSelection.css';

interface ProgramSelectionProps {
    programs: Program[];
    selectedProgramId: string;
    onProgramSelect: (programId: string) => void;
}

export const ProgramSelection: React.FC<ProgramSelectionProps> = ({
    programs,
    selectedProgramId,
    onProgramSelect
}) => {
    return (
        <div className="program-selection">
            <h2>Select Your Program</h2>
            <div className="program-options">
                {programs.map((program) => (
                    <label key={program.id} className="program-option">
                        <input
                            type="radio"
                            name="program"
                            value={program.id}
                            checked={selectedProgramId === program.id}
                            onChange={() => onProgramSelect(program.id)}
                        />
                        <div className="program-info">
                            <div className="program-name">{program.name}</div>
                            <div className="program-duration">{program.duration_months} months</div>
                            <div className="program-price">${program.basePrice}</div>
                        </div>
                    </label>
                ))}
            </div>
        </div>
    );
};
